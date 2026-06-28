<?php

use App\Models\DigitalSellerAlert;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('boxdetail.home', [
        'tenant' => Tenant::with(['landingPage', 'services'])->first(),
        'plans' => Plan::where('active', true)->orderBy('monthly_price')->get(),
    ]);
})->name('home');

Route::get('/admin', function () {
    return view('boxdetail.admin', [
        'tenants' => Tenant::with('plan')->latest()->get(),
        'plans' => Plan::withCount('tenants')->get(),
        'subscriptions' => Subscription::with('tenant')->latest()->get(),
        'alerts' => DigitalSellerAlert::with(['tenant', 'customer'])->latest('detected_at')->get(),
    ]);
})->name('admin');

Route::get('/agendar/{tenant:slug}', function (Tenant $tenant) {
    return view('boxdetail.booking', [
        'tenant' => $tenant->load(['services', 'appointments.customer', 'appointments.service']),
        'orderBumps' => $tenant->orderBumps()->where('is_active', true)->get(),
    ]);
})->name('booking');

Route::get('/loja/{tenant:slug}', function (Tenant $tenant) {
    return view('boxdetail.storefront', [
        'tenant' => $tenant->load(['landingPage', 'services']),
    ]);
})->name('storefront');
