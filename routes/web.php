<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\NewPasswordController;
use App\Http\Controllers\Admin\PasswordResetLinkController;
use App\Http\Controllers\SignupRequestController;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\DigitalSellerAlert;
use App\Models\LandingPage;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantHoliday;
use App\Models\TenantOperatingHour;
use App\Models\TenantServiceCategory;
use App\Models\Vehicle;
use App\Services\VehiclePlateLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

$buildPublicBookingAvailability = function (Tenant $tenant, int $windowDays = 30): array {
    $now = now();
    $startsAt = $now->copy()->startOfDay();
    $endsAt = $now->copy()->addDays($windowDays)->endOfDay();
    $operatingHours = $tenant->operatingHours()->get()->keyBy('day_of_week');
    $holidays = $tenant->holidays()->get();
    $appointments = $tenant->appointments()
        ->whereBetween('scheduled_at', [$startsAt, $endsAt])
        ->whereNotIn('status', ['cancelled', 'canceled'])
        ->get(['scheduled_at', 'ends_at']);
    $services = $tenant->services()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return [
        'generated_at' => $now->toIso8601String(),
        'timezone' => config('app.timezone'),
        'services' => $services->mapWithKeys(function (Service $service) use ($now, $windowDays, $operatingHours, $holidays, $appointments): array {
            $days = [];

            for ($offset = 0; $offset < $windowDays; $offset++) {
                $date = $now->copy()->startOfDay()->addDays($offset);
                $holiday = $holidays->first(fn (TenantHoliday $holiday) => $holiday->repeats_yearly
                    ? $holiday->date->format('m-d') === $date->format('m-d')
                    : $holiday->date->isSameDay($date));

                if ($holiday) {
                    continue;
                }

                $dayHour = $operatingHours->get($date->dayOfWeek);
                $isClosed = $dayHour?->is_closed ?? $date->dayOfWeek === Carbon::SUNDAY;

                if ($isClosed) {
                    continue;
                }

                $opensAt = $dayHour?->opens_at ? substr((string) $dayHour->opens_at, 0, 5) : '08:00';
                $closesAt = $dayHour?->closes_at ? substr((string) $dayHour->closes_at, 0, 5) : '18:00';
                $slot = Carbon::parse($date->toDateString().' '.$opensAt);
                $close = Carbon::parse($date->toDateString().' '.$closesAt);
                $times = [];

                while ($slot->copy()->addMinutes($service->duration_minutes)->lessThanOrEqualTo($close)) {
                    $slotEnd = $slot->copy()->addMinutes($service->duration_minutes);
                    $tooSoon = $slot->lessThan($now->copy()->addMinutes(30));
                    $hasConflict = $appointments->contains(fn (Appointment $appointment) => $slot->lessThan($appointment->ends_at) && $slotEnd->greaterThan($appointment->scheduled_at));

                    if (! $tooSoon && ! $hasConflict) {
                        $times[] = [
                            'value' => $slot->format('H:i'),
                            'label' => $slot->format('H\hi'),
                        ];
                    }

                    $slot->addMinutes(30);
                }

                if ($times !== []) {
                    $days[] = [
                        'date' => $date->toDateString(),
                        'day' => $date->format('d'),
                        'weekday' => Str::upper($date->translatedFormat('D')),
                        'month_label' => Str::ucfirst($date->translatedFormat('F Y')),
                        'date_label' => Str::ucfirst($date->translatedFormat('l, d/m')),
                        'times' => $times,
                    ];
                }
            }

            return [
                $service->id => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'category' => $service->category,
                    'duration' => $service->duration_minutes,
                    'price' => 'R$ '.number_format((float) $service->price, 2, ',', '.'),
                    'days' => $days,
                ],
            ];
        })->all(),
    ];
};

$publicBookingSlotIsAvailable = function (Tenant $tenant, Service $service, string $date, string $time) use ($buildPublicBookingAvailability): bool {
    $availability = $buildPublicBookingAvailability($tenant);
    $serviceAvailability = $availability['services'][$service->id] ?? null;

    if (! $serviceAvailability) {
        return false;
    }

    foreach ($serviceAvailability['days'] as $day) {
        if ($day['date'] !== $date) {
            continue;
        }

        return collect($day['times'])->contains(fn (array $slot) => $slot['value'] === $time);
    }

    return false;
};

Route::get('/', function () {
    return view('garageon.home', [
        'tenant' => Tenant::with(['landingPage', 'services'])->first(),
        'plans' => Plan::where('active', true)->orderBy('monthly_price')->get(),
    ]);
})->name('home');

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
        ->with(['customer', 'service', 'vehicle'])
        ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
        ->orderBy('scheduled_at')
        ->get();

    $topCustomers = $tenant->customers()
        ->withCount(['appointments', 'quotes'])
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
        ->with(['customer', 'service', 'vehicle'])
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
                ->with(['service:id,name', 'vehicle:id,plate,brand,model'])
                ->latest('scheduled_at'),
            'quotes' => fn ($query) => $query
                ->with(['vehicle:id,plate,brand,model', 'items:id,quote_id,name,quantity,unit_price'])
                ->latest('paid_at')
                ->latest('quoted_at')
                ->latest(),
        ])
        ->withCount(['appointments', 'quotes', 'vehicles'])
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
        'vehicles' => ['nullable', 'array'],
        'vehicles.*.id' => ['nullable', 'integer'],
        'vehicles.*.plate' => ['nullable', 'string', 'max:10'],
        'vehicles.*.brand' => ['required', 'string', 'max:80'],
        'vehicles.*.model' => ['required', 'string', 'max:120'],
        'vehicles.*.year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicles.*.color' => ['nullable', 'string', 'max:80'],
    ]);

    $customer->update([
        ...collect($validated)->except('vehicles')->all(),
        'marketing_consent' => $request->boolean('marketing_consent'),
    ]);

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

Route::get('/dashboard/veiculos/placa', function (Request $request, VehiclePlateLookup $lookup) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();
    $plate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('plate')) ?? '');

    abort_if(strlen($plate) !== 7, 422, 'Informe uma placa válida.');

    $vehicle = Vehicle::query()
        ->with('customer')
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

Route::post('/dashboard/agendamentos', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $validated = $request->validate([
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'scheduled_date' => ['required', 'date'],
        'scheduled_time' => ['required', 'date_format:H:i'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $service = Service::query()
        ->where('tenant_id', $tenant->id)
        ->findOrFail($validated['service_id']);

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

    Appointment::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'vehicle_id' => $vehicle->id,
        'source' => 'manual',
        'status' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
        'notes' => $validated['notes'] ?? null,
    ]);

    return back()->with('status', 'Agendamento criado e agenda atualizada.');
})->middleware('auth')->name('appointments.store');

Route::post('/dashboard/vendas', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $validated = $request->validate([
        '_form' => ['required', 'in:sale'],
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)],
        'vehicle_plate' => ['required', 'string', 'max:10'],
        'vehicle_brand' => ['required', 'string', 'max:80'],
        'vehicle_model' => ['required', 'string', 'max:120'],
        'vehicle_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->addYear()->year)],
        'vehicle_color' => ['nullable', 'string', 'max:80'],
        'sold_date' => ['required', 'date'],
        'sold_time' => ['required', 'date_format:H:i'],
        'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        'payment_method' => ['required', 'string', Rule::in(['debito', 'credito', 'pix', 'dinheiro', 'boleto', 'transferencia'])],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $service = Service::query()
        ->where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->findOrFail($validated['service_id']);

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

    Vehicle::updateOrCreate(
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
        'status' => 'approved',
        'total' => $validated['amount'],
        'paid_at' => $paidAt,
        'payment_method' => $validated['payment_method'],
        'channel' => 'cockpit',
        'notes' => $validated['notes'] ?? null,
    ]);

    $quote->items()->create([
        'service_id' => $service->id,
        'name' => $service->name,
        'quantity' => 1,
        'unit_price' => $validated['amount'],
    ]);

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

    return view('garageon.quotes.index', [
        'tenant' => $tenant,
        'services' => $services,
        'quotes' => $quotes,
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

    return view('garageon.quotes.show', [
        'tenant' => $tenant,
        'quote' => $quote,
    ]);
})->middleware('auth')->name('quotes.show');

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

Route::middleware('auth')->prefix('configuracoes')->name('settings.')->group(function () {
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
            'hero_image' => ['nullable', 'url', 'max:2048'],
            'hero_badge_title' => ['nullable', 'string', 'max:80'],
            'hero_badge_body' => ['nullable', 'string', 'max:160'],
            'cta_label' => ['required', 'string', 'max:80'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'analytics_head' => ['nullable', 'string', 'max:10000'],
            'conversion_pixel' => ['nullable', 'string', 'max:10000'],
            'custom_javascript' => ['nullable', 'string', 'max:20000'],
            'published' => ['nullable', 'boolean'],
        ]);

        LandingPage::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'eyebrow' => $validated['eyebrow'] ?? null,
                'headline' => $validated['headline'],
                'subheadline' => $validated['subheadline'],
                'hero_image' => $validated['hero_image'] ?? null,
                'hero_badge_title' => $validated['hero_badge_title'] ?? null,
                'hero_badge_body' => $validated['hero_badge_body'] ?? null,
                'cta_label' => $validated['cta_label'],
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
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $servicePayload = [
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'is_active' => $request->boolean('is_active'),
        ];

        unset($servicePayload['thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $servicePayload['thumbnail_path'] = $request->file('thumbnail')->store("tenants/{$tenant->id}/services", 'public');
        }

        $tenant->services()->create($servicePayload);

        return back()->with('status', 'Serviço criado e pronto para agendamento.');
    })->name('services.store');

    Route::put('/servicos/{service}', function (Request $request, Service $service) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($service->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $servicePayload = [
            ...$validated,
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

Route::post('/loja/{tenant:slug}/agendar', function (Request $request, Tenant $tenant) use ($publicBookingSlotIsAvailable) {
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

    return redirect()
        ->route('storefront', $tenant)
        ->with('booking_status', 'Seu horário foi reservado. A loja vai confirmar os detalhes com você.');
})->name('storefront.booking.store');

Route::get('/loja/{tenant:slug}', function (Tenant $tenant) use ($buildPublicBookingAvailability) {
    return view('garageon.storefront', [
        'tenant' => $tenant->load(['landingPage', 'services', 'serviceCategories']),
        'bookingAvailability' => $buildPublicBookingAvailability($tenant),
    ]);
})->name('storefront');
