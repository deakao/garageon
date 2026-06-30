@php
    $settingsLinks = [
        ['label' => 'Empresa', 'route' => 'settings.company'],
        ['label' => 'Landing', 'route' => 'settings.landing'],
        ['label' => 'Serviços', 'route' => 'settings.services'],
        ['label' => 'Horários', 'route' => 'settings.hours'],
        ['label' => 'Feriados', 'route' => 'settings.holidays'],
    ];
@endphp

<header class="rounded-[28px] border border-white/10 bg-white/[.035] p-5 shadow-2xl shadow-black/20 backdrop-blur sm:p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('dashboard') }}" class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">← Voltar ao cockpit</a>
            <h1 class="mt-3 font-orbitron text-3xl font-black text-white">Configurações</h1>
            <p class="mt-2 text-sm text-zinc-400">Ajuste dados, serviços e regras que controlam a agenda da {{ $tenant->name }}.</p>
        </div>

        <nav class="flex flex-wrap gap-2" aria-label="Configurações da empresa">
            @foreach ($settingsLinks as $link)
                <a href="{{ route($link['route']) }}" class="rounded-full px-4 py-2 text-sm font-black transition focus:outline-none focus:ring-2 focus:ring-yellow-300 {{ request()->routeIs($link['route']) ? 'bg-yellow-300 text-black' : 'border border-white/10 bg-white/[.06] text-zinc-200 hover:border-yellow-300 hover:text-yellow-300' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>

@if (session('status'))
    <p class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
@endif
