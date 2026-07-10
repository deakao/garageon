<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrador - GarageON</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-zinc-950 text-white antialiased">
    <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
        <header class="mb-10 flex flex-col gap-5 border-b border-white/10 pb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('home') }}" class="font-orbitron text-sm uppercase tracking-[.35em] text-yellow-300">GarageON Admin</a>
                <h1 class="mt-4 font-orbitron text-4xl font-black">Gestão de lojas, planos e mensalidades</h1>
                <p class="mt-3 text-zinc-400">Visão da plataforma para controlar tenants, receita recorrente e oportunidades detectadas pela IA.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="rounded-full border border-white/15 px-5 py-3 text-center text-sm font-bold hover:border-yellow-300 hover:text-yellow-200">Voltar ao site</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-white px-5 py-3 text-sm font-bold text-black hover:bg-yellow-300 sm:w-auto">Sair</button>
                </form>
            </div>
        </header>

        <section class="grid gap-4 md:grid-cols-4">
            <div class="rounded-3xl border border-white/10 bg-white/[.04] p-6">
                <p class="text-sm text-zinc-400">Lojas ativas</p>
                <strong class="mt-2 block font-orbitron text-4xl text-yellow-300">{{ $tenants->count() }}</strong>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/[.04] p-6">
                <p class="text-sm text-zinc-400">Planos</p>
                <strong class="mt-2 block font-orbitron text-4xl">{{ $plans->count() }}</strong>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/[.04] p-6">
                <p class="text-sm text-zinc-400">MRR demonstrativo</p>
                <strong class="mt-2 block font-orbitron text-4xl">R$ {{ number_format($tenants->sum(fn ($tenant) => (float) ($tenant->plan?->monthly_price ?? 0)), 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-3xl border border-yellow-300/30 bg-yellow-300 p-6 text-black">
                <p class="text-sm font-bold">Alertas de venda</p>
                <strong class="mt-2 block font-orbitron text-4xl">{{ $alerts->count() }}</strong>
            </div>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
            <div class="rounded-3xl border border-white/10 bg-black p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="font-orbitron text-2xl font-bold">Lojas cadastradas</h2>
                    <span class="rounded-full bg-yellow-300/10 px-3 py-1 text-xs text-yellow-200">multi-tenant</span>
                </div>
                <div class="space-y-3">
                    @foreach ($tenants as $tenant)
                        <div class="rounded-2xl border border-white/10 bg-zinc-900 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-bold">{{ $tenant->name }}</p>
                                    <p class="text-sm text-zinc-400">{{ $tenant->primary_domain }} · {{ $tenant->whatsapp_phone }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('booking', $tenant) }}" class="rounded-full bg-white px-4 py-2 text-sm font-bold text-black">Agenda</a>
                                    <a href="{{ route('storefront', $tenant) }}" class="rounded-full bg-yellow-300 px-4 py-2 text-sm font-bold text-black">Landing</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-black p-6">
                <h2 class="font-orbitron text-2xl font-bold">Vendedor Digital</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($alerts as $alert)
                        <article class="rounded-2xl border border-yellow-300/20 bg-yellow-300/10 p-4">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="font-bold text-yellow-100">{{ $alert->title }}</p>
                                <span class="rounded-full bg-black px-3 py-1 text-xs uppercase text-yellow-300">{{ $alert->priority }}</span>
                            </div>
                            <p class="text-sm text-zinc-300">{{ $alert->customer?->name }} · {{ $alert->tenant?->name }}</p>
                            <p class="mt-3 text-sm leading-6 text-zinc-100">"{{ $alert->suggested_message }}"</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-white/10 bg-white/[.04] p-6">
            <h2 class="font-orbitron text-2xl font-bold">Planos comerciais</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach ($plans as $plan)
                    <article class="rounded-2xl bg-zinc-950 p-5">
                        <p class="font-orbitron text-lg text-yellow-300">{{ $plan->name }}</p>
                        <p class="mt-3 text-3xl font-black">R$ {{ number_format((float) $plan->monthly_price, 2, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-zinc-400">{{ $plan->tenants_count }} loja(s) neste plano</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</body>
</html>
