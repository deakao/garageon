<?php

use App\Enums\AttendantProvider;
use App\Enums\AttendantTone;
use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\NewPasswordController;
use App\Http\Controllers\Admin\PasswordResetLinkController;
use App\Http\Controllers\Chat\ConnectionController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ServiceImportController;
use App\Http\Controllers\SignupRequestController;
use App\Mail\QuoteSharedMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\DigitalSellerAlert;
use App\Models\LandingPage;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\QuoteFunnelAutomation;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantHoliday;
use App\Models\TenantOperatingHour;
use App\Models\TenantServiceCategory;
use App\Models\Vehicle;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\AttendantPromptBuilder;
use App\Services\AttendantUsage;
use App\Services\BookingAvailability;
use App\Services\EvolutionGoClient;
use App\Services\QuoteFunnelAutomationRunner;
use App\Services\VehiclePlateLookup;
use App\Support\WhatsappPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

$buildPublicBookingAvailability = function (Tenant $tenant, int $windowDays = 30): array {
    return app(BookingAvailability::class)->forTenant($tenant, $windowDays);
};

$publicBookingSlotIsAvailable = function (Tenant $tenant, Service $service, string $date, string $time): bool {
    return app(BookingAvailability::class)->slotIsAvailable($tenant, $service, $date, $time);
};

$normalizeCustomDomain = function (?string $domain): ?string {
    $domain = Str::lower(trim((string) $domain));
    $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
    $domain = Str::before($domain, '/');
    $domain = Str::before($domain, ':');
    $domain = trim($domain, '.');

    return $domain !== '' ? $domain : null;
};

$findTenantByCustomDomain = function (Request $request) use ($normalizeCustomDomain): ?Tenant {
    $host = $normalizeCustomDomain($request->getHost());

    if (! $host) {
        return null;
    }

    $domainCandidates = collect([
        $host,
        Str::startsWith($host, 'www.') ? Str::after($host, 'www.') : 'www.'.$host,
    ])->filter()->unique()->values();

    return Tenant::query()
        ->whereIn('primary_domain', $domainCandidates)
        ->first();
};

$renderStorefront = function (Tenant $tenant, bool $customDomain = false) use ($buildPublicBookingAvailability) {
    return view('garageon.storefront', [
        'tenant' => $tenant->load(['landingPage', 'services', 'serviceCategories']),
        'bookingAvailability' => $buildPublicBookingAvailability($tenant),
        'customDomain' => $customDomain,
    ]);
};

$storePublicBooking = function (Request $request, Tenant $tenant, string $redirectUrl) use ($publicBookingSlotIsAvailable) {
    $validated = $request->validateWithBag('booking', [
        'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
        'scheduled_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(30)->toDateString()],
        'scheduled_time' => ['required', 'date_format:H:i'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'customer_email' => ['required', 'email', 'max:255'],
        'vehicle_plate' => ['nullable', 'string', 'max:10'],
        'vehicle_brand' => ['nullable', 'string', 'max:80'],
        'vehicle_model' => ['nullable', 'string', 'max:120'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $service = Service::query()
        ->where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->findOrFail($validated['service_id']);

    if (! $publicBookingSlotIsAvailable($tenant, $service, $validated['scheduled_date'], $validated['scheduled_time'])) {
        return back()
            ->withErrors(['scheduled_time' => 'Esse horário acabou de ficar indisponível. Escolha outro horário.'], 'booking')
            ->withInput();
    }

    $vehicle = null;
    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate'] ?? '') ?? '');

    $identifiedVehicle = $vehiclePlate !== ''
        ? Vehicle::query()
            ->where('tenant_id', $tenant->id)
            ->where('plate', $vehiclePlate)
            ->with('customer')
            ->get()
            ->first(fn (Vehicle $vehicle) => Str::lower((string) $vehicle->customer?->email) === Str::lower($validated['customer_email']))
        : null;

    $customer = $identifiedVehicle?->customer;

    if (! $customer && $vehiclePlate === '') {
        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone', $validated['customer_phone'])
            ->first();
    }

    if ($customer) {
        $customer->update([
            'name' => $validated['customer_name'],
            'email' => $validated['customer_email'],
        ]);
    } else {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'],
            'email' => $validated['customer_email'],
            'tags' => ['landing-page'],
        ]);
    }

    if ($vehiclePlate !== '' && filled($validated['vehicle_brand'] ?? null) && filled($validated['vehicle_model'] ?? null)) {
        if ($identifiedVehicle) {
            $identifiedVehicle->update([
                'customer_id' => $customer->id,
                'brand' => $validated['vehicle_brand'],
                'model' => $validated['vehicle_model'],
            ]);

            $vehicle = $identifiedVehicle;
        } else {
            $vehicle = Vehicle::create([
                'tenant_id' => $tenant->id,
                'plate' => $vehiclePlate,
                'customer_id' => $customer->id,
                'brand' => $validated['vehicle_brand'],
                'model' => $validated['vehicle_model'],
            ]);
        }
    }

    $scheduledAt = Carbon::parse($validated['scheduled_date'].' '.$validated['scheduled_time']);

    Appointment::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'vehicle_id' => $vehicle?->id,
        'source' => 'landing-page',
        'status' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
        'notes' => $validated['notes'] ?? null,
    ]);

    return redirect($redirectUrl)
        ->with('booking_status', 'Seu horário foi reservado. A loja vai confirmar os detalhes com você.');
};

$storePublicWhatsappLead = function (Request $request, Tenant $tenant) {
    $storePhone = WhatsappPhone::normalize($tenant->whatsapp_phone);

    abort_if($storePhone === '', 404);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
    ]);

    $leadPhone = WhatsappPhone::normalize($validated['phone']);

    if (strlen($leadPhone) < 12) {
        return response()->json([
            'message' => 'Informe um WhatsApp válido com DDD.',
            'errors' => ['phone' => ['Informe um WhatsApp válido com DDD.']],
        ], 422);
    }

    $customer = Customer::query()
        ->where('tenant_id', $tenant->id)
        ->get()
        ->first(fn (Customer $item) => WhatsappPhone::normalize($item->phone) === $leadPhone);

    $leadTags = ['lead', 'landing-whatsapp'];

    if ($customer) {
        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'tags' => collect($customer->tags ?? [])
                ->merge($leadTags)
                ->unique()
                ->values()
                ->all(),
        ]);
    } else {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'tags' => $leadTags,
        ]);
    }

    $message = "Olá! Vim pela landing page da {$tenant->name}. Meu nome é {$customer->name}.";

    return response()->json([
        'ok' => true,
        'whatsapp_url' => 'https://wa.me/'.$storePhone.'?text='.rawurlencode($message),
        'customer_id' => $customer->id,
    ]);
};

Route::get('/', function (Request $request) use ($findTenantByCustomDomain, $renderStorefront) {
    if ($tenant = $findTenantByCustomDomain($request)) {
        return $renderStorefront($tenant, true);
    }

    return view('garageon.home', [
        'tenant' => Tenant::with(['landingPage', 'services'])->first(),
        'plans' => Plan::where('active', true)->orderBy('monthly_price')->get(),
    ]);
})->name('home');

Route::post('/agendar', function (Request $request) use ($findTenantByCustomDomain, $storePublicBooking) {
    $tenant = $findTenantByCustomDomain($request);

    abort_unless($tenant, 404);

    return $storePublicBooking($request, $tenant, url('/'));
})->name('storefront.custom.booking.store');

Route::post('/whatsapp-lead', function (Request $request) use ($findTenantByCustomDomain, $storePublicWhatsappLead) {
    $tenant = $findTenantByCustomDomain($request);

    abort_unless($tenant, 404);

    return $storePublicWhatsappLead($request, $tenant);
})->name('storefront.custom.whatsapp-lead.store');

Route::get('/cadastro', [SignupRequestController::class, 'create'])->name('signup.create');
Route::post('/cadastro', [SignupRequestController::class, 'store'])->name('signup.store');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('/admin/esqueci-minha-senha', [PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('password.request');
Route::post('/admin/esqueci-minha-senha', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');
Route::get('/admin/redefinir-senha/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');
Route::post('/admin/redefinir-senha', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/configurar-depois', [OnboardingController::class, 'showSkip'])->name('skip');
    Route::post('/configurar-depois', [OnboardingController::class, 'dismiss'])->name('dismiss');

    Route::put('/hours', [OnboardingController::class, 'updateHours'])->name('hours.update');
    Route::post('/services', [OnboardingController::class, 'storeService'])->name('services.store');
    Route::put('/logo', [OnboardingController::class, 'updateLogo'])->name('logo.update');
    Route::put('/attendant', [OnboardingController::class, 'updateAttendant'])->name('attendant.update');
    Route::put('/landing', [OnboardingController::class, 'updateLanding'])->name('landing.update');

    Route::post('/{step}/skip', [OnboardingController::class, 'skipStep'])->name('skip-step');
    Route::get('/{step}', [OnboardingController::class, 'show'])->name('show');
});

Route::get('/dashboard', function () {
    if (auth()->user()->isPlatformAdmin()) {
        return redirect()->route('admin');
    }

    $tenant = auth()->user()->tenants()
        ->with('plan')
        ->withCount(['services', 'customers', 'appointments', 'quotes'])
        ->firstOrFail();

    $today = now();
    $monthStart = $today->copy()->startOfMonth();
    $monthEnd = $today->copy()->endOfMonth();

    $monthQuotes = $tenant->quotes()
        ->whereBetween('created_at', [$monthStart, $monthEnd])
        ->get();

    $monthSales = $tenant->quotes()
        ->where('status', 'approved')
        ->whereNotNull('paid_at')
        ->whereBetween('paid_at', [$monthStart, $monthEnd])
        ->get();

    $salesByPayment = $monthSales->groupBy('payment_method')->map->sum('total');

    $todayAppointments = $tenant->appointments()
        ->whereDate('scheduled_at', $today)
        ->get();

    $calendarAppointments = $tenant->appointments()
        ->with(['customer' => fn ($query) => $query->withSum('loyaltyLedger as loyalty_points', 'points'), 'service', 'serviceItems', 'vehicle'])
        ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
        ->orderBy('scheduled_at')
        ->get();

    $topCustomers = $tenant->customers()
        ->withCount(['appointments', 'quotes'])
        ->withSum('loyaltyLedger as loyalty_points', 'points')
        ->orderByDesc('appointments_count')
        ->orderByDesc('quotes_count')
        ->limit(5)
        ->get();

    $services = $tenant->services()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('garageon.dashboard', [
        'tenant' => $tenant,
        'services' => $services,
        'dashboardStats' => [
            'month_quotes_total' => $monthSales->sum('total'),
            'month_quotes_pending' => $monthQuotes->whereIn('status', ['sent', 'pending'])->count(),
            'month_quotes_approved' => $monthQuotes->whereIn('status', ['approved', 'accepted'])->count(),
            'month_sales_by_payment' => [
                'debito' => (float) ($salesByPayment->get('debito', 0)),
                'credito' => (float) ($salesByPayment->get('credito', 0)),
                'pix' => (float) ($salesByPayment->get('pix', 0)),
                'dinheiro' => (float) ($salesByPayment->get('dinheiro', 0)),
                'boleto' => (float) ($salesByPayment->get('boleto', 0)),
                'transferencia' => (float) ($salesByPayment->get('transferencia', 0)),
            ],
            'today_appointments' => $todayAppointments->count(),
            'today_completed_appointments' => $todayAppointments->whereIn('status', ['completed', 'done'])->count(),
            'today_open_appointments' => $todayAppointments->whereNotIn('status', ['completed', 'done', 'cancelled'])->count(),
            'calendar_appointments' => $calendarAppointments,
            'top_customers' => $topCustomers,
        ],
    ]);
})->middleware('auth')->name('dashboard');

Route::get('/dashboard/agenda', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $view = $request->query('view');
    $view = in_array($view, ['month', 'week', 'day'], true) ? $view : 'month';

    $anchor = null;

    if ($request->filled('date')) {
        try {
            $anchor = Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay();
        } catch (Throwable) {
            $anchor = null;
        }
    }

    $anchor ??= now()->startOfDay();

    $months = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $workHours = range(8, 18, 2);

    if ($view === 'month') {
        $rangeStart = $anchor->copy()->startOfMonth();
        $rangeEnd = $rangeStart->copy()->endOfMonth();
        $title = $months[$rangeStart->month].' / '.$rangeStart->year;
    } elseif ($view === 'week') {
        $rangeStart = $anchor->copy()->subDays($anchor->dayOfWeek);
        $rangeEnd = $rangeStart->copy()->addDays(6)->endOfDay();
        $title = $rangeStart->format('d/m').' a '.$rangeEnd->format('d/m').' · '.$months[$rangeEnd->month].' / '.$rangeEnd->year;
    } else {
        $rangeStart = $anchor->copy()->startOfDay();
        $rangeEnd = $anchor->copy()->endOfDay();
        $title = $anchor->format('d').' de '.$months[$anchor->month].' / '.$anchor->year;
    }

    $appointments = $tenant->appointments()
        ->with(['customer' => fn ($query) => $query->withSum('loyaltyLedger as loyalty_points', 'points'), 'service', 'serviceItems', 'vehicle'])
        ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
        ->orderBy('scheduled_at')
        ->get();

    $appointmentsByDate = $appointments->groupBy(fn ($appointment) => $appointment->scheduled_at->toDateString());

    $panel = match ($view) {
        'month' => view('garageon.dashboard.calendar-panels.month', [
            'monthStart' => $rangeStart,
            'daysInMonth' => $rangeStart->daysInMonth,
            'firstWeekday' => $rangeStart->dayOfWeek,
            'weekdays' => $weekdays,
            'appointmentsByDate' => $appointmentsByDate,
            'today' => now(),
        ]),
        'week' => view('garageon.dashboard.calendar-panels.week', [
            'weekStart' => $rangeStart,
            'workHours' => $workHours,
            'weekdays' => $weekdays,
            'appointmentsByDate' => $appointmentsByDate,
        ]),
        default => view('garageon.dashboard.calendar-panels.day', [
            'workHours' => $workHours,
            'dayAppointments' => $appointments,
            'dayDate' => $anchor,
        ]),
    };

    return response()->json([
        'view' => $view,
        'date' => $rangeStart->toDateString(),
        'title' => $title,
        'panel' => $panel->render(),
    ]);
})->middleware('auth')->name('dashboard.agenda');

Route::get('/dashboard/clientes', function () {
    if (auth()->user()->isPlatformAdmin()) {
        return redirect()->route('admin');
    }

    $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();

    $customers = $tenant->customers()
        ->with([
            'vehicles:id,customer_id,plate,brand,model,year,color',
            'appointments' => fn ($query) => $query
                ->with(['service:id,name', 'serviceItems', 'vehicle:id,plate,brand,model'])
                ->latest('scheduled_at'),
            'quotes' => fn ($query) => $query
                ->with(['vehicle:id,plate,brand,model', 'items:id,quote_id,name,quantity,unit_price'])
                ->latest('paid_at')
                ->latest('quoted_at')
                ->latest(),
        ])
        ->withCount(['appointments', 'quotes', 'vehicles'])
        ->withSum('loyaltyLedger as loyalty_points', 'points')
        ->latest()
        ->get();

    $activeToday = $tenant->appointments()
        ->whereDate('scheduled_at', now())
        ->whereNotIn('status', ['cancelled'])
        ->distinct()
        ->count('customer_id');

    return view('garageon.customers.index', [
        'tenant' => $tenant,
        'customers' => $customers,
        'customerStats' => [
            'total' => $customers->count(),
            'new_this_month' => $customers->where('created_at', '>=', now()->startOfMonth())->count(),
            'with_vehicles' => $customers->where('vehicles_count', '>', 0)->count(),
            'active_today' => $activeToday,
        ],
    ]);
})->middleware('auth')->name('customers.index');

Route::post('/dashboard/clientes', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    $request->merge(['vehicles' => collect($request->input('vehicles', []))->filter(fn ($vehicle) => collect($vehicle)->except('id')->filter()->isNotEmpty())->map(fn ($vehicle) => [
        ...$vehicle,
        'brand' => blank($vehicle['brand'] ?? null) ? null : $vehicle['brand'],
        'model' => blank($vehicle['model'] ?? null) ? null : $vehicle['model'],
    ])->values()->all()]);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
        'marketing_consent' => ['nullable', 'boolean'],
        'vehicles' => ['nullable', 'array'],
        'vehicles.*.plate' => ['nullable', 'string', 'max:10'],
        'vehicles.*.brand' => ['required', 'string', 'max:80'],
        'vehicles.*.model' => ['required', 'string', 'max:120'],
        'vehicles.*.year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicles.*.color' => ['nullable', 'string', 'max:80'],
    ]);

    $customer = $tenant->customers()->create([
        ...collect($validated)->except('vehicles')->all(),
        'marketing_consent' => $request->boolean('marketing_consent'),
        'tags' => ['manual'],
    ]);

    foreach ($validated['vehicles'] ?? [] as $vehicle) {
        $customer->vehicles()->create([
            'tenant_id' => $tenant->id,
            'plate' => filled($vehicle['plate'] ?? null) ? Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $vehicle['plate'])) : null,
            'brand' => $vehicle['brand'],
            'model' => $vehicle['model'],
            'year' => $vehicle['year'] ?? null,
            'color' => $vehicle['color'] ?? null,
        ]);
    }

    return back()->with('status', 'Cliente cadastrado na base.');
})->middleware('auth')->name('customers.store');

Route::put('/dashboard/clientes/{customer}', function (Request $request, Customer $customer) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($customer->tenant_id === $tenant->id, 404);
    $request->merge(['vehicles' => collect($request->input('vehicles', []))->filter(fn ($vehicle) => collect($vehicle)->except('id')->filter()->isNotEmpty())->map(fn ($vehicle) => [
        ...$vehicle,
        'brand' => blank($vehicle['brand'] ?? null) ? null : $vehicle['brand'],
        'model' => blank($vehicle['model'] ?? null) ? null : $vehicle['model'],
    ])->values()->all()]);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
        'marketing_consent' => ['nullable', 'boolean'],
        'loyalty_points' => ['nullable', 'integer', 'min:0', 'max:999999'],
        'vehicles' => ['nullable', 'array'],
        'vehicles.*.id' => ['nullable', 'integer'],
        'vehicles.*.plate' => ['nullable', 'string', 'max:10'],
        'vehicles.*.brand' => ['required', 'string', 'max:80'],
        'vehicles.*.model' => ['required', 'string', 'max:120'],
        'vehicles.*.year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicles.*.color' => ['nullable', 'string', 'max:80'],
    ]);

    $customer->update([
        ...collect($validated)->except(['loyalty_points', 'vehicles'])->all(),
        'marketing_consent' => $request->boolean('marketing_consent'),
    ]);

    if ($request->has('loyalty_points')) {
        $targetPoints = (int) $validated['loyalty_points'];
        $currentPoints = (int) $customer->loyaltyLedger()->sum('points');
        $pointsDelta = $targetPoints - $currentPoints;

        if ($pointsDelta !== 0) {
            $customer->loyaltyLedger()->create([
                'tenant_id' => $tenant->id,
                'type' => 'adjustment',
                'points' => $pointsDelta,
                'reason' => 'Ajuste manual na tela do cliente',
            ]);
        }
    }

    $keptVehicleIds = [];

    foreach ($validated['vehicles'] ?? [] as $vehicle) {
        $vehicleData = [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate' => filled($vehicle['plate'] ?? null) ? Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $vehicle['plate'])) : null,
            'brand' => $vehicle['brand'],
            'model' => $vehicle['model'],
            'year' => $vehicle['year'] ?? null,
            'color' => $vehicle['color'] ?? null,
        ];

        if (filled($vehicle['id'] ?? null)) {
            $customerVehicle = $customer->vehicles()->whereKey($vehicle['id'])->firstOrFail();
            $customerVehicle->update($vehicleData);
            $keptVehicleIds[] = $customerVehicle->id;
        } else {
            $keptVehicleIds[] = $customer->vehicles()->create($vehicleData)->id;
        }
    }

    $customer->vehicles()->when($keptVehicleIds !== [], fn ($query) => $query->whereNotIn('id', $keptVehicleIds))->delete();

    return back()->with('status', 'Cliente atualizado.');
})->middleware('auth')->name('customers.update');

Route::delete('/dashboard/clientes/{customer}', function (Customer $customer) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($customer->tenant_id === $tenant->id, 404);

    $customer->delete();

    return back()->with('status', 'Cliente excluído com sucesso.');
})->middleware('auth')->name('customers.destroy');

Route::middleware('auth')->prefix('dashboard/chat')->name('chat.')->group(function () {
    /**
     * Monta os dados de conversas/contatos/mensagens do chat.
     * Compartilhado entre o render inicial (index) e o polling (stream).
     *
     * @return array<string, mixed>
     */
    $buildChatData = function (Tenant $tenant, Request $request): array {
        $conversations = $tenant->whatsappConversations()
            ->with(['customer.vehicles'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        $customers = $tenant->customers()
            ->with('vehicles')
            ->orderBy('name')
            ->get();

        $selectedConversation = null;
        $selectedCustomer = null;

        if ($request->integer('conversation')) {
            $selectedConversation = $conversations->firstWhere('id', $request->integer('conversation'))
                ?? $tenant->whatsappConversations()->with(['customer.vehicles'])->find($request->integer('conversation'));
        }

        if (! $selectedConversation && $request->integer('customer')) {
            $selectedCustomer = $customers->firstWhere('id', $request->integer('customer'))
                ?? $tenant->customers()->with('vehicles')->find($request->integer('customer'));
        }

        if (! $selectedConversation && ! $selectedCustomer) {
            $selectedConversation = $conversations->first();
            $selectedCustomer = $selectedConversation ? null : $customers->first();
        }

        if ($selectedConversation && $selectedConversation->unread_count > 0) {
            $selectedConversation->forceFill(['unread_count' => 0])->save();
        }

        $messages = $selectedConversation
            ? $selectedConversation->messages()->orderBy('occurred_at')->orderBy('id')->get()
            : collect();

        $conversationCustomerIds = $conversations->pluck('customer_id')->filter()->all();
        $customersWithoutConversation = $customers->reject(fn (Customer $customer) => in_array($customer->id, $conversationCustomerIds, true));

        return [
            'conversations' => $conversations,
            'customersWithoutConversation' => $customersWithoutConversation,
            'selectedConversation' => $selectedConversation,
            'selectedCustomer' => $selectedCustomer,
            'messages' => $messages,
            'chatStats' => [
                'total_conversations' => $conversations->count(),
                'unread' => (int) $tenant->whatsappConversations()->sum('unread_count'),
                'messages_today' => $tenant->whatsappMessages()->whereDate('occurred_at', now())->count(),
            ],
        ];
    };

    Route::get('/', function (Request $request, EvolutionGoClient $evolution) use ($buildChatData) {
        if (auth()->user()->isPlatformAdmin()) {
            return redirect()->route('admin');
        }

        $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();
        $connection = WhatsappConnection::query()->firstOrCreate([
            'tenant_id' => $tenant->id,
        ], [
            'instance_name' => 'garageon-'.$tenant->id,
            'status' => 'unconfigured',
        ]);

        $webhookUrl = $evolution->webhookUrl($connection->webhook_secret);

        if ($connection->webhook_url !== $webhookUrl) {
            $connection->forceFill(['webhook_url' => $webhookUrl])->save();
        }

        $connectionUpdates = [];

        if (! $connection->instance_id && $connection->status !== 'unconfigured') {
            $connectionUpdates['status'] = 'unconfigured';
        }

        if ($connection->qrcode || $connection->qrcode_code) {
            $connectionUpdates['qrcode'] = null;
            $connectionUpdates['qrcode_code'] = null;
        }

        if ($connectionUpdates !== []) {
            $connection->forceFill($connectionUpdates)->save();
        }

        $chatData = $buildChatData($tenant, $request);

        return view('garageon.chat.index', array_merge([
            'tenant' => $tenant,
            'connection' => $connection->fresh(),
            'evolutionConfigured' => $evolution->configured(),
            'webhookUrl' => $webhookUrl,
        ], $chatData));
    })->name('index');

    Route::get('/stream', function (Request $request) use ($buildChatData) {
        abort_if(auth()->user()->isPlatformAdmin(), 403);

        $tenant = auth()->user()->tenants()->firstOrFail();
        $chatData = $buildChatData($tenant, $request);

        return response()->json([
            'list' => view('garageon.chat._list', $chatData)->render(),
            'messages' => view('garageon.chat._messages', $chatData)->render(),
            'stats' => $chatData['chatStats'],
            'selected' => $chatData['selectedConversation']?->id,
        ]);
    })->name('stream');

    Route::post('/conectar', [ConnectionController::class, 'connect'])->name('connect');

    Route::delete('/desconectar', [ConnectionController::class, 'disconnect'])->name('disconnect');

    Route::post('/renovar-qr', [ConnectionController::class, 'renewQr'])->name('qr.renew');

    Route::post('/sincronizar', [ConnectionController::class, 'sync'])->name('sync');

    Route::post('/mensagens', function (Request $request, EvolutionGoClient $evolution) {
        if (auth()->user()->isPlatformAdmin()) {
            abort(403);
        }

        $tenant = auth()->user()->tenants()->firstOrFail();
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenant->id)],
            'body' => ['required', 'string', 'max:2000'],
            'return_to' => ['nullable', 'in:back'],
        ]);

        $connection = $tenant->whatsappConnection()->first();

        if (! $connection?->instance_id || $connection->status !== 'connected') {
            return back()->withErrors(['whatsapp' => 'Conecte uma instância WhatsApp antes de enviar mensagens.']);
        }

        $conversation = null;
        $customer = null;

        if (! empty($validated['conversation_id'])) {
            $conversation = $tenant->whatsappConversations()->find($validated['conversation_id']);
            $customer = $conversation?->customer;
        }

        if (! $conversation && ! empty($validated['customer_id'])) {
            $customer = $tenant->customers()->findOrFail($validated['customer_id']);
            $phone = WhatsappPhone::normalize($customer->phone);

            if ($phone === '') {
                return back()->withErrors(['whatsapp' => 'Este cliente não tem um WhatsApp válido cadastrado.']);
            }

            $conversation = WhatsappConversation::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'contact_phone' => $phone,
            ], [
                'customer_id' => $customer->id,
                'contact_name' => $customer->name,
                'status' => 'open',
            ]);
        }

        if (! $conversation) {
            return back()->withErrors(['whatsapp' => 'Escolha um cliente ou conversa antes de enviar.']);
        }

        $result = $evolution->sendText($connection, $conversation->contact_phone, $validated['body']);
        $payload = $result['payload'] ?? [];
        $externalId = data_get($payload, 'data.Info.ID') ?: data_get($payload, 'messageId');
        $status = $result['successful'] ? 'sent' : 'failed';

        $messageData = [
            'tenant_id' => $tenant->id,
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer?->id ?: $conversation->customer_id,
            'external_id' => $externalId,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $validated['body'],
            'status' => $status,
            'payload' => $payload ?: ['error' => $result['message'] ?? null],
            'occurred_at' => now(),
        ];

        if ($externalId) {
            WhatsappMessage::query()->updateOrCreate([
                'tenant_id' => $tenant->id,
                'external_id' => $externalId,
            ], $messageData);
        } else {
            WhatsappMessage::query()->create($messageData);
        }

        $conversation->forceFill([
            'customer_id' => $customer?->id ?: $conversation->customer_id,
            'contact_name' => $customer?->name ?: $conversation->contact_name,
            'last_message' => $validated['body'],
            'last_message_at' => now(),
            'status' => 'open',
        ])->save();

        $redirect = ($validated['return_to'] ?? null) === 'back'
            ? back()
            : redirect()->route('chat.index', ['conversation' => $conversation->id]);

        if (! $result['successful']) {
            return $redirect
                ->with('status', 'Mensagem registrada, mas a Evolution não confirmou o envio.')
                ->withErrors(['whatsapp' => $result['message'] ?? 'Não consegui enviar essa mensagem agora.']);
        }

        return $redirect->with('status', 'Mensagem enviada pelo WhatsApp.');
    })->name('messages.store');
});

Route::get('/dashboard/veiculos/placa', function (Request $request, VehiclePlateLookup $lookup) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    $plate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('plate')) ?? '');

    abort_if(strlen($plate) !== 7, 422, 'Informe uma placa válida.');

    $vehicle = Vehicle::query()
        ->with(['customer' => fn ($query) => $query->withSum('loyaltyLedger as loyalty_points', 'points')])
        ->where('tenant_id', $tenant->id)
        ->where('plate', $plate)
        ->latest()
        ->first();

    if ($vehicle) {
        return response()->json([
            'plate' => $vehicle->plate,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'color' => $vehicle->color,
            'source' => 'garageon',
            'customer_name' => $vehicle->customer?->name,
            'customer_phone' => $vehicle->customer?->phone,
            'customer_email' => $vehicle->customer?->email,
            'customer_loyalty_points' => (int) ($vehicle->customer?->loyalty_points ?? 0),
        ]);
    }

    $vehicle = $lookup->lookup($plate);

    if ($vehicle) {
        return response()->json($vehicle);
    }

    return response()->json([
        'message' => 'Não encontrei essa placa agora. Você pode preencher os dados manualmente.',
    ], 404);
})->middleware('auth')->name('vehicles.lookup');

$validateDashboardAppointment = function (Request $request, Tenant $tenant, bool $withStatus = false): array {
    $rules = [
        '_form' => ['nullable', 'in:appointment'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'services' => ['required', 'array', 'min:1'],
        'services.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
        'services.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'scheduled_date' => ['required', 'date'],
        'scheduled_time' => ['required', 'date_format:H:i'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];

    if ($withStatus) {
        $rules['status'] = ['required', Rule::in(['pending', 'scheduled', 'completed', 'cancelled'])];
    }

    return $request->validate($rules);
};

$buildDashboardAppointment = function (array $validated, Tenant $tenant, ?Customer $customer = null): array {
    $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->values();
    $services = Service::query()
        ->where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->whereIn('id', $serviceIds)
        ->get()
        ->keyBy('id');

    $appointmentServices = [];
    $totalDurationMinutes = 0;

    foreach ($validated['services'] as $line) {
        $service = $services->get($line['service_id']);

        if (! $service) {
            continue;
        }

        $quantity = (int) $line['quantity'];
        $totalDurationMinutes += $service->duration_minutes * $quantity;

        $appointmentServices[] = [
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => $quantity,
            'duration_minutes' => $service->duration_minutes,
            'unit_price' => $service->price,
        ];
    }

    $primaryService = $services->get($validated['services'][0]['service_id']);

    if ($customer) {
        $customer->update([
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'],
        ]);
    } else {
        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone', $validated['customer_phone'])
            ->first();

        if ($customer) {
            $customer->update(['name' => $validated['customer_name']]);
        } else {
            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'tags' => ['agenda'],
            ]);
        }
    }

    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate']) ?? '');

    $vehicle = Vehicle::updateOrCreate(
        [
            'tenant_id' => $tenant->id,
            'plate' => $vehiclePlate,
        ],
        [
            'customer_id' => $customer->id,
            'brand' => $validated['vehicle_brand'],
            'model' => $validated['vehicle_model'],
            'year' => $validated['vehicle_year'] ?? null,
            'color' => $validated['vehicle_color'] ?? null,
        ]
    );

    $scheduledAt = Carbon::parse($validated['scheduled_date'].' '.$validated['scheduled_time']);

    return [$customer, $vehicle, $primaryService, $appointmentServices, $scheduledAt, $totalDurationMinutes];
};

Route::post('/dashboard/agendamentos', function (Request $request) use ($validateDashboardAppointment, $buildDashboardAppointment) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    $validated = $validateDashboardAppointment($request, $tenant);
    [$customer, $vehicle, $primaryService, $appointmentServices, $scheduledAt, $totalDurationMinutes] = $buildDashboardAppointment($validated, $tenant);

    $appointment = Appointment::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $primaryService->id,
        'vehicle_id' => $vehicle->id,
        'source' => 'manual',
        'status' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'ends_at' => $scheduledAt->copy()->addMinutes($totalDurationMinutes),
        'notes' => $validated['notes'] ?? null,
    ]);

    $appointment->serviceItems()->createMany($appointmentServices);

    return back()->with('status', 'Agendamento criado e agenda atualizada.');
})->middleware('auth')->name('appointments.store');

Route::put('/dashboard/agendamentos/{appointment}', function (Request $request, Appointment $appointment) use ($validateDashboardAppointment, $buildDashboardAppointment) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($appointment->tenant_id === $tenant->id, 404);

    $validated = $validateDashboardAppointment($request, $tenant, true);
    [$customer, $vehicle, $primaryService, $appointmentServices, $scheduledAt, $totalDurationMinutes] = $buildDashboardAppointment($validated, $tenant, $appointment->customer);

    $appointment->update([
        'customer_id' => $customer->id,
        'service_id' => $primaryService->id,
        'vehicle_id' => $vehicle->id,
        'status' => $validated['status'],
        'scheduled_at' => $scheduledAt,
        'ends_at' => $scheduledAt->copy()->addMinutes($totalDurationMinutes),
        'notes' => $validated['notes'] ?? null,
    ]);

    $appointment->serviceItems()->delete();
    $appointment->serviceItems()->createMany($appointmentServices);

    return back()->with('status', 'Agendamento atualizado e agenda sincronizada.');
})->middleware('auth')->name('appointments.update');

Route::delete('/dashboard/agendamentos/{appointment}', function (Appointment $appointment) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($appointment->tenant_id === $tenant->id, 404);

    $appointment->delete();

    return back()->with('status', 'Agendamento excluído da agenda.');
})->middleware('auth')->name('appointments.destroy');

Route::post('/dashboard/vendas', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $validated = $request->validate([
        '_form' => ['required', 'in:sale'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'services' => ['required', 'array', 'min:1'],
        'services.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)],
        'services.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'sold_date' => ['required', 'date'],
        'sold_time' => ['required', 'date_format:H:i'],
        'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        'loyalty_points_to_debit' => ['nullable', 'integer', 'min:0', 'max:999999'],
        'payment_method' => ['required', 'string', Rule::in(['debito', 'credito', 'pix', 'dinheiro', 'boleto', 'transferencia'])],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $pointsToDebit = (int) ($validated['loyalty_points_to_debit'] ?? 0);

    $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->values();
    $services = Service::query()
        ->where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->whereIn('id', $serviceIds)
        ->get()
        ->keyBy('id');

    $vehicle = null;
    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate'] ?? '') ?? '');

    $identifiedVehicle = $vehiclePlate !== ''
        ? Vehicle::query()
            ->where('tenant_id', $tenant->id)
            ->where('plate', $vehiclePlate)
            ->with('customer')
            ->latest()
            ->first()
        : null;

    $customer = $identifiedVehicle?->customer;

    if (! $customer) {
        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone', $validated['customer_phone'])
            ->first();
    }

    $availablePoints = $customer ? (int) $customer->loyaltyLedger()->sum('points') : 0;

    if ($pointsToDebit > $availablePoints) {
        return back()
            ->withErrors(['loyalty_points_to_debit' => 'O cliente tem '.number_format($availablePoints, 0, ',', '.').' pts disponíveis.'])
            ->withInput();
    }

    if ($customer) {
        $customer->update(['name' => $validated['customer_name']]);
    } else {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'],
            'tags' => ['venda'],
        ]);
    }

    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate']) ?? '');

    $vehicle = Vehicle::updateOrCreate(
        [
            'tenant_id' => $tenant->id,
            'plate' => $vehiclePlate,
        ],
        [
            'customer_id' => $customer->id,
            'brand' => $validated['vehicle_brand'],
            'model' => $validated['vehicle_model'],
            'year' => $validated['vehicle_year'] ?? null,
            'color' => $validated['vehicle_color'] ?? null,
        ]
    );

    $paidAt = Carbon::parse($validated['sold_date'].' '.$validated['sold_time']);

    $quote = Quote::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'approved',
        'total' => $validated['amount'],
        'paid_at' => $paidAt,
        'payment_method' => $validated['payment_method'],
        'channel' => 'cockpit',
        'notes' => $validated['notes'] ?? null,
    ]);

    $items = [];
    $loyaltyPoints = 0;

    foreach ($validated['services'] as $line) {
        $service = $services->get($line['service_id']);

        if (! $service) {
            continue;
        }

        $quantity = (int) $line['quantity'];
        $loyaltyPoints += $service->loyalty_points * $quantity;

        $items[] = [
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $service->price,
        ];
    }

    $quote->items()->createMany($items);

    if ($loyaltyPoints > 0) {
        $customer->loyaltyLedger()->create([
            'tenant_id' => $tenant->id,
            'type' => 'earn',
            'points' => $loyaltyPoints,
            'reason' => 'Venda #'.$quote->id,
        ]);
    }

    if ($pointsToDebit > 0) {
        $customer->loyaltyLedger()->create([
            'tenant_id' => $tenant->id,
            'type' => 'redeem',
            'points' => -$pointsToDebit,
            'reason' => 'Débito na venda #'.$quote->id,
        ]);
    }

    return back()->with('status', 'Venda registrada e incluída no resumo do mês.');
})->middleware('auth')->name('sales.store');

Route::get('/dashboard/orcamentos', function () {
    if (auth()->user()->isPlatformAdmin()) {
        return redirect()->route('admin');
    }

    $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();
    $services = $tenant->services()->where('is_active', true)->orderBy('name')->get();

    $quotes = $tenant->quotes()
        ->whereNot('status', 'approved')
        ->with(['customer', 'vehicle', 'items'])
        ->withCount('items')
        ->latest('quoted_at')
        ->latest()
        ->get();

    $quoteColumns = [
        'sent' => 'Enviado',
        'pending' => 'Aguardando',
        'accepted' => 'Aceito',
        'expired' => 'Expirado',
    ];

    $quotesByStatus = collect($quoteColumns)
        ->mapWithKeys(fn ($label, $status) => [$status => $quotes->where('status', $status)->values()]);

    return view('garageon.quotes.index', [
        'tenant' => $tenant,
        'services' => $services,
        'quotes' => $quotes,
        'quoteColumns' => $quoteColumns,
        'quotesByStatus' => $quotesByStatus,
        'quoteStats' => [
            'total' => $quotes->count(),
            'sent_this_month' => $quotes->filter(fn ($quote) => $quote->status === 'sent' && $quote->created_at->isCurrentMonth())->count(),
            'pending_value' => $quotes->whereIn('status', ['sent', 'pending'])->sum('total'),
            'accepted' => $quotes->where('status', 'accepted')->count(),
        ],
    ]);
})->middleware('auth')->name('quotes.index');

Route::post('/dashboard/orcamentos', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $validated = $request->validate([
        '_form' => ['required', 'in:quote'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'quoted_date' => ['required', 'date'],
        'quoted_time' => ['required', 'date_format:H:i'],
        'services' => ['required', 'array', 'min:1'],
        'services.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)],
        'services.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $customer = Customer::query()
        ->where('tenant_id', $tenant->id)
        ->where('phone', $validated['customer_phone'])
        ->first();

    if ($customer) {
        $customer->update(['name' => $validated['customer_name']]);
    } else {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'],
            'tags' => ['orçamento'],
        ]);
    }

    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate']) ?? '');

    $vehicle = Vehicle::updateOrCreate(
        [
            'tenant_id' => $tenant->id,
            'plate' => $vehiclePlate,
        ],
        [
            'customer_id' => $customer->id,
            'brand' => $validated['vehicle_brand'],
            'model' => $validated['vehicle_model'],
            'year' => $validated['vehicle_year'] ?? null,
            'color' => $validated['vehicle_color'] ?? null,
        ]
    );

    $quotedAt = Carbon::parse($validated['quoted_date'].' '.$validated['quoted_time']);
    $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->values();
    $services = Service::query()
        ->where('tenant_id', $tenant->id)
        ->whereIn('id', $serviceIds)
        ->get()
        ->keyBy('id');

    $total = 0;
    $items = [];

    foreach ($validated['services'] as $line) {
        $service = $services->get($line['service_id']);

        if (! $service) {
            continue;
        }

        $quantity = (int) $line['quantity'];
        $total += (float) $service->price * $quantity;

        $items[] = [
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $service->price,
        ];
    }

    $quote = Quote::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'sent',
        'total' => $total,
        'quoted_at' => $quotedAt,
        'valid_until' => $quotedAt->copy()->addDays(7)->toDateString(),
        'channel' => 'cockpit',
        'notes' => $validated['notes'] ?? null,
    ]);

    $quote->items()->createMany($items);

    app(QuoteFunnelAutomationRunner::class)->dispatchForStage($quote, 'sent');

    return redirect()
        ->route('quotes.show', $quote)
        ->with('status', 'Orçamento gerado e pronto para apresentar ao cliente.');
})->middleware('auth')->name('quotes.store');

Route::get('/dashboard/orcamentos/{quote}', function (Quote $quote) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($quote->tenant_id === $tenant->id, 404);

    $quote->load(['customer', 'vehicle', 'items.service']);
    $connection = $tenant->whatsappConnection()->first();

    return view('garageon.quotes.show', [
        'tenant' => $tenant,
        'quote' => $quote,
        'whatsappConnected' => filled($connection?->instance_id) && $connection->status === 'connected',
    ]);
})->middleware('auth')->name('quotes.show');

Route::post('/dashboard/orcamentos/{quote}/email', function (Quote $quote) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($quote->tenant_id === $tenant->id, 404);

    $quote->load(['tenant', 'customer', 'vehicle', 'items.service']);

    if (blank($quote->customer->email)) {
        return back()->withErrors(['email' => 'Cadastre um e-mail no cliente antes de enviar o orçamento.']);
    }

    Mail::mailer(config('mail.default'))->to($quote->customer->email)->send(new QuoteSharedMail($quote));

    return back()->with('status', 'Orçamento enviado por e-mail.');
})->middleware('auth')->name('quotes.email');

Route::patch('/dashboard/orcamentos/{quote}/status', function (Request $request, Quote $quote) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($quote->tenant_id === $tenant->id, 404);
    abort_if($quote->status === 'approved', 422);

    $validated = $request->validate([
        'status' => ['required', 'in:sent,pending,accepted,expired'],
    ]);

    $previousStatus = $quote->status;

    $quote->update(['status' => $validated['status']]);

    if ($previousStatus !== $validated['status']) {
        app(QuoteFunnelAutomationRunner::class)->dispatchForStage($quote, $validated['status']);
    }

    if ($request->expectsJson()) {
        return response()->json([
            'status' => $quote->status,
            'message' => 'Status do orçamento atualizado.',
        ]);
    }

    return back()->with('status', 'Status do orçamento atualizado.');
})->middleware('auth')->name('quotes.status');

Route::put('/dashboard/orcamentos/{quote}', function (Request $request, Quote $quote) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($quote->tenant_id === $tenant->id, 404);
    abort_if($quote->status === 'approved', 422);

    $validated = $request->validate([
        'status' => ['required', 'in:sent,pending,accepted,expired'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'quoted_date' => ['required', 'date'],
        'quoted_time' => ['required', 'date_format:H:i'],
        'valid_until' => ['nullable', 'date'],
        'services' => ['required', 'array', 'min:1'],
        'services.*.service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)],
        'services.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $previousStatus = $quote->status;

    $quote->customer->update([
        'name' => $validated['customer_name'],
        'phone' => $validated['customer_phone'],
    ]);

    $vehiclePlate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $validated['vehicle_plate']) ?? '');

    $vehicle = Vehicle::updateOrCreate(
        [
            'tenant_id' => $tenant->id,
            'plate' => $vehiclePlate,
        ],
        [
            'customer_id' => $quote->customer_id,
            'brand' => $validated['vehicle_brand'],
            'model' => $validated['vehicle_model'],
            'year' => $validated['vehicle_year'] ?? null,
            'color' => $validated['vehicle_color'] ?? null,
        ]
    );

    $quotedAt = Carbon::parse($validated['quoted_date'].' '.$validated['quoted_time']);
    $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->values();
    $services = Service::query()
        ->where('tenant_id', $tenant->id)
        ->whereIn('id', $serviceIds)
        ->get()
        ->keyBy('id');

    $total = 0;
    $items = [];

    foreach ($validated['services'] as $line) {
        $service = $services->get($line['service_id']);

        if (! $service) {
            continue;
        }

        $quantity = (int) $line['quantity'];
        $total += (float) $service->price * $quantity;

        $items[] = [
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $service->price,
        ];
    }

    $quote->update([
        'vehicle_id' => $vehicle->id,
        'status' => $validated['status'],
        'total' => $total,
        'quoted_at' => $quotedAt,
        'valid_until' => $validated['valid_until'] ?? $quote->valid_until,
        'notes' => $validated['notes'] ?? null,
    ]);

    $quote->items()->delete();
    $quote->items()->createMany($items);

    if ($previousStatus !== $validated['status']) {
        app(QuoteFunnelAutomationRunner::class)->dispatchForStage($quote, $validated['status']);
    }

    return back()->with('status', 'Orçamento atualizado com sucesso.');
})->middleware('auth')->name('quotes.update');

Route::delete('/dashboard/orcamentos/{quote}', function (Quote $quote) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    abort_unless($quote->tenant_id === $tenant->id, 404);
    abort_if($quote->status === 'approved', 422);

    $quote->delete();

    return back()->with('status', 'Orçamento removido da base.');
})->middleware('auth')->name('quotes.destroy');

Route::get('/orcamento/{token}', function (string $token) {
    $quote = Quote::query()
        ->where('public_token', $token)
        ->with(['tenant', 'customer', 'vehicle', 'items.service'])
        ->firstOrFail();

    return view('garageon.quotes.public', [
        'tenant' => $quote->tenant,
        'quote' => $quote,
    ]);
})->name('quotes.public');

Route::middleware('auth')->prefix('configuracoes')->name('settings.')->group(function () use ($normalizeCustomDomain) {
    Route::get('/empresa', function () {
        $tenant = auth()->user()->tenants()
            ->with('plan')
            ->withCount(['users', 'customers', 'services'])
            ->firstOrFail();

        return view('garageon.settings.company', [
            'tenant' => $tenant,
            'companyStats' => [
                'team' => $tenant->users_count,
                'customers' => $tenant->customers_count,
                'services' => $tenant->services_count,
            ],
        ]);
    })->name('company');

    Route::put('/empresa', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'primary_domain' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store("tenants/{$tenant->id}/logo", 'public');
        }

        unset($validated['logo']);

        $tenant->update($validated);

        return back()->with('status', 'Dados da empresa atualizados.');
    })->name('company.update');

    Route::get('/landing-page', function () {
        $tenant = auth()->user()->tenants()->with(['landingPage', 'services', 'serviceCategories'])->firstOrFail();

        return view('garageon.settings.landing', [
            'tenant' => $tenant,
            'landingPage' => $tenant->landingPage,
        ]);
    })->name('landing');

    Route::put('/landing-page', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['required', 'string', 'max:255'],
            'hero_image' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail) use ($tenant) {
                    if (filter_var($value, FILTER_VALIDATE_URL) || Str::startsWith((string) $value, "/storage/tenants/{$tenant->id}/landing/")) {
                        return;
                    }

                    $fail('Informe uma URL válida ou envie uma imagem.');
                },
            ],
            'hero_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero_badge_title' => ['nullable', 'string', 'max:80'],
            'hero_badge_body' => ['nullable', 'string', 'max:160'],
            'cta_label' => ['required', 'string', 'max:80'],
            'testimonials' => ['nullable', 'array', 'max:12'],
            'testimonials.*.name' => ['nullable', 'string', 'max:80'],
            'testimonials.*.role' => ['nullable', 'string', 'max:80'],
            'testimonials.*.quote' => ['nullable', 'string', 'max:500'],
            'testimonials.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'analytics_head' => ['nullable', 'string', 'max:10000'],
            'conversion_pixel' => ['nullable', 'string', 'max:10000'],
            'custom_javascript' => ['nullable', 'string', 'max:20000'],
            'published' => ['nullable', 'boolean'],
        ]);

        $landingPage = $tenant->landingPage;
        $heroImage = $validated['hero_image'] ?? null;

        if ($request->hasFile('hero_image_file')) {
            $oldHeroImagePath = $landingPage?->hero_image ? Str::after($landingPage->hero_image, '/storage/') : null;

            if ($oldHeroImagePath && $oldHeroImagePath !== $landingPage->hero_image && Str::startsWith($oldHeroImagePath, "tenants/{$tenant->id}/landing/")) {
                Storage::disk('public')->delete($oldHeroImagePath);
            }

            $heroImage = '/storage/'.$request->file('hero_image_file')->store("tenants/{$tenant->id}/landing", 'public');
        }

        $testimonials = collect($validated['testimonials'] ?? [])
            ->map(fn (array $item) => [
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'quote' => trim((string) ($item['quote'] ?? '')),
                'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
            ])
            ->filter(fn (array $item) => $item['name'] !== '' && $item['quote'] !== '')
            ->values()
            ->all();

        LandingPage::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'eyebrow' => $validated['eyebrow'] ?? null,
                'headline' => $validated['headline'],
                'subheadline' => $validated['subheadline'],
                'hero_image' => $heroImage,
                'hero_badge_title' => $validated['hero_badge_title'] ?? null,
                'hero_badge_body' => $validated['hero_badge_body'] ?? null,
                'cta_label' => $validated['cta_label'],
                'testimonials' => $testimonials,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
                'seo_keywords' => $validated['seo_keywords'] ?? null,
                'analytics_head' => $validated['analytics_head'] ?? null,
                'conversion_pixel' => $validated['conversion_pixel'] ?? null,
                'custom_javascript' => $validated['custom_javascript'] ?? null,
                'published_at' => $request->boolean('published') ? now() : null,
            ],
        );

        return back()->with('status', 'Landing page atualizada e pronta para vender.');
    })->name('landing.update');

    Route::get('/dominio', function (Request $request) use ($normalizeCustomDomain) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $platformHost = $normalizeCustomDomain(config('services.garageon.cname_target') ?: parse_url(config('app.url'), PHP_URL_HOST) ?: $request->getHost());

        return view('garageon.settings.domain', [
            'tenant' => $tenant,
            'platformHost' => $platformHost,
            'customDomainUrl' => $tenant->primary_domain ? 'https://'.$tenant->primary_domain : null,
        ]);
    })->name('domain');

    Route::put('/dominio', function (Request $request) use ($normalizeCustomDomain) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $domain = $normalizeCustomDomain($request->input('primary_domain'));
        $platformHost = $normalizeCustomDomain(config('services.garageon.cname_target') ?: parse_url(config('app.url'), PHP_URL_HOST) ?: $request->getHost());

        $request->merge(['primary_domain' => $domain]);

        $validated = $request->validate([
            'primary_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,}$/',
                Rule::notIn(array_filter([$platformHost, $platformHost ? 'www.'.$platformHost : null, 'localhost'])),
            ],
        ], [
            'primary_domain.regex' => 'Informe um domínio válido, como www.sualoja.com.br.',
            'primary_domain.not_in' => 'Use um domínio próprio da loja, não o domínio da plataforma.',
        ]);

        if ($domain) {
            $domainCandidates = collect([
                $domain,
                Str::startsWith($domain, 'www.') ? Str::after($domain, 'www.') : 'www.'.$domain,
            ])->filter()->unique()->values();

            $alreadyUsed = Tenant::query()
                ->where('id', '!=', $tenant->id)
                ->whereIn('primary_domain', $domainCandidates)
                ->exists();

            if ($alreadyUsed) {
                return back()
                    ->withErrors(['primary_domain' => 'Esse domínio já está conectado a outra loja.'])
                    ->withInput();
            }
        }

        $tenant->update(['primary_domain' => $validated['primary_domain'] ?? null]);

        return back()->with('status', $domain ? 'Domínio salvo. Assim que o CNAME propagar, ele abrirá a landing da loja.' : 'Domínio removido. A landing continua disponível pelo link padrão.');
    })->name('domain.update');

    Route::get('/servicos', function () {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $services = $tenant->services()->orderBy('name')->get();
        $categories = $tenant->serviceCategories()->orderBy('name')->get();

        return view('garageon.settings.services', [
            'tenant' => $tenant,
            'services' => $services,
            'categories' => $categories,
            'categoryUsage' => $services->groupBy('category')->map->count(),
            'serviceStats' => [
                'total' => $services->count(),
                'active' => $services->where('is_active', true)->count(),
                'categories' => $categories->count(),
            ],
        ]);
    })->name('services');

    Route::post('/servicos', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'loyalty_points' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $servicePayload = [
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'loyalty_points' => (int) ($validated['loyalty_points'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];

        unset($servicePayload['thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $servicePayload['thumbnail_path'] = $request->file('thumbnail')->store("tenants/{$tenant->id}/services", 'public');
        }

        $tenant->services()->create($servicePayload);

        return back()->with('status', 'Serviço criado e pronto para agendamento.');
    })->name('services.store');

    Route::post('/servicos/importar', [ServiceImportController::class, 'store'])->name('services.import');
    Route::get('/servicos/exemplo/{format}', [ServiceImportController::class, 'example'])->name('services.example');

    Route::put('/servicos/{service}', function (Request $request, Service $service) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($service->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'loyalty_points' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $servicePayload = [
            ...$validated,
            'loyalty_points' => (int) ($validated['loyalty_points'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];

        unset($servicePayload['thumbnail']);

        if ($request->hasFile('thumbnail')) {
            if ($service->thumbnail_path) {
                Storage::disk('public')->delete($service->thumbnail_path);
            }

            $servicePayload['thumbnail_path'] = $request->file('thumbnail')->store("tenants/{$tenant->id}/services", 'public');
        }

        $service->update($servicePayload);

        return back()->with('status', 'Serviço atualizado.');
    })->name('services.update');

    Route::post('/servicos/categorias', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validateWithBag('categories', [
            'name' => ['required', 'string', 'max:80', Rule::unique('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
        ]);

        $tenant->serviceCategories()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return back()->with('status', 'Categoria criada para organizar seu catálogo.');
    })->name('service-categories.store');

    Route::put('/servicos/categorias/{category}', function (Request $request, TenantServiceCategory $category) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($category->tenant_id === $tenant->id, 404);

        $validated = $request->validateWithBag('categories', [
            'name' => ['required', 'string', 'max:80', Rule::unique('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)->ignore($category->id)],
        ]);

        $oldName = $category->name;

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        $tenant->services()
            ->where('category', $oldName)
            ->update(['category' => $validated['name']]);

        return back()->with('status', 'Categoria atualizada no catálogo.');
    })->name('service-categories.update');

    Route::delete('/servicos/{service}', function (Service $service) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($service->tenant_id === $tenant->id, 404);

        $service->update(['is_active' => false]);

        return back()->with('status', 'Serviço desativado sem apagar o histórico.');
    })->name('services.destroy');

    Route::delete('/servicos/categorias/{category}', function (TenantServiceCategory $category) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($category->tenant_id === $tenant->id, 404);

        if ($tenant->services()->where('category', $category->name)->exists()) {
            return back()->withErrors([
                'delete' => 'Não é possível excluir uma categoria com serviços vinculados.',
            ], 'categories');
        }

        $category->delete();

        return back()->with('status', 'Categoria removida do catálogo.');
    })->name('service-categories.destroy');

    Route::get('/horarios', function () {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $hours = $tenant->operatingHours()->orderBy('day_of_week')->get()->keyBy('day_of_week');

        return view('garageon.settings.hours', [
            'tenant' => $tenant,
            'hours' => $hours,
        ]);
    })->name('hours');

    Route::put('/horarios', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['hours'] as $day => $hour) {
            $isClosed = (bool) ($hour['is_closed'] ?? false);

            TenantOperatingHour::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day_of_week' => (int) $day],
                [
                    'opens_at' => $isClosed ? null : ($hour['opens_at'] ?? '08:00'),
                    'closes_at' => $isClosed ? null : ($hour['closes_at'] ?? '18:00'),
                    'is_closed' => $isClosed,
                ],
            );
        }

        return back()->with('status', 'Horários de funcionamento atualizados.');
    })->name('hours.update');

    Route::get('/feriados', function () {
        $tenant = auth()->user()->tenants()->firstOrFail();

        return view('garageon.settings.holidays', [
            'tenant' => $tenant,
            'holidays' => $tenant->holidays()->orderBy('date')->get(),
        ]);
    })->name('holidays');

    Route::post('/feriados', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date'],
            'repeats_yearly' => ['nullable', 'boolean'],
        ]);

        $tenant->holidays()->create([
            ...$validated,
            'repeats_yearly' => $request->boolean('repeats_yearly'),
        ]);

        return back()->with('status', 'Feriado bloqueado na agenda.');
    })->name('holidays.store');

    Route::delete('/feriados/{holiday}', function (TenantHoliday $holiday) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($holiday->tenant_id === $tenant->id, 404);

        $holiday->delete();

        return back()->with('status', 'Feriado removido.');
    })->name('holidays.destroy');

    Route::get('/atendente', function (AttendantUsage $usage) {
        $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();

        $attendant = VirtualAttendant::query()->firstOrNew(['tenant_id' => $tenant->id]);
        $attendant->setRelation('tenant', $tenant);

        return view('garageon.settings.attendant', [
            'tenant' => $tenant,
            'attendant' => $attendant,
            'toneOptions' => AttendantTone::options(),
            'providerOptions' => AttendantProvider::options(),
            'promptPreview' => app(AttendantPromptBuilder::class)->build($attendant),
            'dailyLimit' => $usage->limitFor($tenant),
            'usedToday' => $usage->usedToday($tenant),
            'requiresOwnKey' => (bool) $tenant->plan?->requires_own_key,
        ]);
    })->name('attendant');

    Route::put('/atendente', function (Request $request) {
        $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tone' => ['required', Rule::enum(AttendantTone::class)],
            'provider' => ['required', Rule::enum(AttendantProvider::class)],
            'model' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:5000'],
            'require_booking_confirmation' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attendant = VirtualAttendant::query()->firstOrNew(['tenant_id' => $tenant->id]);

        $attendant->fill([
            'name' => $validated['name'],
            'tone' => $validated['tone'],
            'provider' => $validated['provider'],
            'model' => ($validated['model'] ?? null) ?: null,
            'context' => ($validated['context'] ?? null) ?: null,
            'require_booking_confirmation' => $request->boolean('require_booking_confirmation'),
            'is_active' => $request->boolean('is_active'),
        ]);

        // Só sobrescreve a API key quando o usuário digita uma nova (campo vem vazio ao editar).
        if (filled($validated['api_key'] ?? null)) {
            $attendant->api_key = $validated['api_key'];
        }

        // Para ligar, precisa de alguma chave utilizável: a do tenant ou a da plataforma.
        if ($attendant->is_active && ! filled($attendant->resolveApiKey())) {
            return back()
                ->withInput()
                ->withErrors(['api_key' => 'Informe a API key do provedor para ligar o atendimento automático.']);
        }

        // No plano "traga sua própria chave", ligar exige a chave do próprio tenant.
        if ($attendant->is_active && $tenant->plan?->requires_own_key && ! $attendant->usesOwnKey()) {
            return back()
                ->withInput()
                ->withErrors(['api_key' => 'Seu plano exige que você informe sua própria API key de IA para ligar o atendente.']);
        }

        $attendant->save();

        return back()->with('status', 'Piloto automático atualizado.');
    })->name('attendant.update');

    $quoteFunnelPlaceholders = [
        '{{cliente}}' => 'Nome do cliente',
        '{{loja}}' => 'Nome da loja',
        '{{orcamento}}' => 'Número do orçamento',
        '{{valor}}' => 'Valor total',
        '{{placa}}' => 'Placa do veículo',
        '{{veiculo}}' => 'Marca e modelo',
        '{{link}}' => 'Link público do orçamento',
        '{{status}}' => 'Status atual',
    ];

    $validateQuoteFunnelAutomation = function (Request $request): array {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'stage' => ['required', Rule::in(array_keys(QuoteFunnelAutomation::STAGES))],
            'channel' => ['required', Rule::in(array_keys(QuoteFunnelAutomation::CHANNELS))],
            'delay_value' => ['required', 'integer', 'min:0', 'max:365'],
            'delay_unit' => ['required', Rule::in(array_keys(QuoteFunnelAutomation::DELAY_UNITS))],
            'subject' => ['nullable', 'string', 'max:180'],
            'message_template' => ['required', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['subject'] = $validated['channel'] === 'email'
            ? ($validated['subject'] ?? null)
            : null;

        return $validated;
    };

    Route::get('/funil-orcamentos', function () use ($quoteFunnelPlaceholders) {
        $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();

        $automations = $tenant->quoteFunnelAutomations()
            ->get()
            ->sortBy([
                fn ($automation) => array_search($automation->stage, array_keys(QuoteFunnelAutomation::STAGES), true),
                fn ($automation) => $automation->delayInMinutes(),
                'name',
            ])
            ->values();

        return view('garageon.settings.quote-funnel', [
            'tenant' => $tenant,
            'automations' => $automations,
            'placeholders' => $quoteFunnelPlaceholders,
            'stats' => [
                'total' => $automations->count(),
                'active' => $automations->where('is_active', true)->count(),
                'whatsapp' => $automations->where('channel', 'whatsapp')->count(),
                'email' => $automations->where('channel', 'email')->count(),
            ],
        ]);
    })->name('quote-funnel');

    Route::post('/funil-orcamentos', function (Request $request) use ($validateQuoteFunnelAutomation) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $validated = $validateQuoteFunnelAutomation($request);

        $tenant->quoteFunnelAutomations()->create($validated);

        return redirect()
            ->route('settings.quote-funnel')
            ->with('status', 'Automação criada com sucesso.');
    })->name('quote-funnel.store');

    Route::put('/funil-orcamentos/{automation}', function (Request $request, QuoteFunnelAutomation $automation) use ($validateQuoteFunnelAutomation) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($automation->tenant_id === $tenant->id, 404);

        $automation->update($validateQuoteFunnelAutomation($request));

        return redirect()
            ->route('settings.quote-funnel')
            ->with('status', 'Automação atualizada com sucesso.');
    })->name('quote-funnel.update');

    Route::delete('/funil-orcamentos/{automation}', function (QuoteFunnelAutomation $automation) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($automation->tenant_id === $tenant->id, 404);

        $automation->delete();

        return redirect()
            ->route('settings.quote-funnel')
            ->with('status', 'Automação removida.');
    })->name('quote-funnel.destroy');
});

Route::get('/admin', function () {
    return view('garageon.admin', [
        'tenants' => Tenant::with('plan')->latest()->get(),
        'plans' => Plan::withCount('tenants')->get(),
        'subscriptions' => Subscription::with('tenant')->latest()->get(),
        'alerts' => DigitalSellerAlert::with(['tenant', 'customer'])->latest('detected_at')->get(),
    ]);
})->middleware(['auth', 'platform.admin'])->name('admin');

Route::get('/agendar/{tenant:slug}', function (Tenant $tenant) {
    return view('garageon.booking', [
        'tenant' => $tenant->load(['services', 'appointments.customer', 'appointments.service']),
        'orderBumps' => $tenant->orderBumps()->where('is_active', true)->get(),
    ]);
})->name('booking');

Route::post('/loja/{tenant:slug}/agendar', function (Request $request, Tenant $tenant) use ($storePublicBooking) {
    return $storePublicBooking($request, $tenant, route('storefront', $tenant));
})->name('storefront.booking.store');

Route::post('/loja/{tenant:slug}/whatsapp-lead', function (Request $request, Tenant $tenant) use ($storePublicWhatsappLead) {
    return $storePublicWhatsappLead($request, $tenant);
})->name('storefront.whatsapp-lead.store');

Route::get('/loja/{tenant:slug}', function (Tenant $tenant) use ($renderStorefront) {
    return $renderStorefront($tenant);
})->name('storefront');
