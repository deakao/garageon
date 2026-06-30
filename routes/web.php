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
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantHoliday;
use App\Models\TenantOperatingHour;
use App\Models\TenantServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    $todayAppointments = $tenant->appointments()
        ->whereDate('scheduled_at', $today)
        ->get();

    $calendarAppointments = $tenant->appointments()
        ->with(['customer', 'service'])
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
            'month_quotes_total' => $monthQuotes->sum('total'),
            'month_quotes_pending' => $monthQuotes->whereIn('status', ['sent', 'pending'])->count(),
            'month_quotes_approved' => $monthQuotes->whereIn('status', ['approved', 'accepted'])->count(),
            'today_appointments' => $todayAppointments->count(),
            'today_completed_appointments' => $todayAppointments->whereIn('status', ['completed', 'done'])->count(),
            'today_open_appointments' => $todayAppointments->whereNotIn('status', ['completed', 'done', 'cancelled'])->count(),
            'calendar_appointments' => $calendarAppointments,
            'top_customers' => $topCustomers,
        ],
    ]);
})->middleware('auth')->name('dashboard');

Route::post('/dashboard/agendamentos', function (Request $request) {
    if (auth()->user()->isPlatformAdmin()) {
        abort(403);
    }

    $tenant = auth()->user()->tenants()->firstOrFail();

    $validated = $request->validate([
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('tenant_id', $tenant->id)],
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

    $scheduledAt = Carbon::parse($validated['scheduled_date'].' '.$validated['scheduled_time']);

    Appointment::create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'source' => 'manual',
        'status' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
        'notes' => $validated['notes'] ?? null,
    ]);

    return back()->with('status', 'Agendamento criado e agenda atualizada.');
})->middleware('auth')->name('appointments.store');

Route::middleware('auth')->prefix('configuracoes')->name('settings.')->group(function () {
    Route::get('/empresa', function () {
        $tenant = auth()->user()->tenants()->with('plan')->firstOrFail();

        return view('garageon.settings.company', ['tenant' => $tenant]);
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
        $tenant = auth()->user()->tenants()->with('landingPage')->firstOrFail();

        return view('garageon.settings.landing', [
            'tenant' => $tenant,
            'landingPage' => $tenant->landingPage,
        ]);
    })->name('landing');

    Route::put('/landing-page', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['required', 'string', 'max:255'],
            'cta_label' => ['required', 'string', 'max:80'],
            'sections' => ['required', 'array', 'size:2'],
            'sections.*.title' => ['required', 'string', 'max:120'],
            'sections.*.body' => ['required', 'string', 'max:255'],
            'published' => ['nullable', 'boolean'],
        ]);

        LandingPage::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'headline' => $validated['headline'],
                'subheadline' => $validated['subheadline'],
                'cta_label' => $validated['cta_label'],
                'sections' => $validated['sections'],
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
        ]);
    })->name('services');

    Route::post('/servicos', function (Request $request) {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenant->services()->create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Serviço criado e pronto para agendamento.');
    })->name('services.store');

    Route::put('/servicos/{service}', function (Request $request, Service $service) {
        $tenant = auth()->user()->tenants()->firstOrFail();
        abort_unless($service->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

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

Route::get('/loja/{tenant:slug}', function (Tenant $tenant) {
    return view('garageon.storefront', [
        'tenant' => $tenant->load(['landingPage', 'services']),
    ]);
})->name('storefront');
