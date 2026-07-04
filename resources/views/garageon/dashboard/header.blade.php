@php
    $quickActions ??= [
        ['label' => 'Dashboard', 'component' => 'tabler-layout-dashboard', 'href' => route('dashboard'), 'primary' => false],
        ['label' => 'Nova Venda', 'component' => 'tabler-plus', 'overlay' => 'sale-modal', 'primary' => true],
        
        ['label' => 'Orçamentos', 'component' => 'tabler-file-invoice', 'href' => route('quotes.index'), 'primary' => false],
        ['label' => 'Clientes', 'component' => 'tabler-users', 'href' => route('customers.index'), 'primary' => false],
    ];

    $settingsLinks = [
        ['label' => 'Empresa', 'route' => 'settings.company', 'component' => 'tabler-building-store'],
        ['label' => 'Serviços', 'route' => 'settings.services', 'component' => 'tabler-list-check'],
        ['label' => 'Horários', 'route' => 'settings.hours', 'component' => 'tabler-clock'],
        ['label' => 'Landing page', 'route' => 'settings.landing', 'component' => 'tabler-world-www'],
        ['label' => 'Domínio', 'route' => 'settings.domain', 'component' => 'tabler-world-www'],
        ['label' => 'Feriados', 'route' => 'settings.holidays', 'component' => 'tabler-calendar-off'],
    ];

    $isSettingsRoute = request()->routeIs('settings.*');
@endphp

<header class="relative z-40 rounded-[28px] border border-white/10 bg-white/[.035] p-5 shadow-2xl shadow-black/20 backdrop-blur sm:p-6">
    <div class="flex flex-col gap-4 border-white/10 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col gap-3">
            <img src="{{ $tenant->logoUrl() ?? asset('img/logo-horizontal.png') }}" alt="{{ $tenant->logoUrl() ? 'Logo da '.$tenant->name : 'GarageON' }}" class="h-9 max-w-56 object-contain sm:h-11">
            <p class="font-orbitron text-sm font-black uppercase tracking-[.18em] text-yellow-300">{{ $tenant->name }} já está ON.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            @foreach ($quickActions as $action)
                @if (! empty($action['overlay']))
                    <button type="button" data-overlay-open="{{ $action['overlay'] }}" class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2.5 text-xs font-black transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-yellow-300/20 {{ $action['primary'] ? 'bg-yellow-300 text-black shadow-lg shadow-yellow-300/15 hover:bg-white' : 'border border-white/10 bg-black/30 text-zinc-100 hover:border-yellow-300 hover:text-yellow-300' }}">
                        <x-dynamic-component :component="$action['component']" class="h-4 w-4" stroke-width="2.4" />
                        {{ $action['label'] }}
                    </button>
                @else
                    <a href="{{ $action['href'] }}" class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2.5 text-xs font-black transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-yellow-300/20 {{ $action['primary'] ? 'bg-yellow-300 text-black shadow-lg shadow-yellow-300/15 hover:bg-white' : 'border border-white/10 bg-black/30 text-zinc-100 hover:border-yellow-300 hover:text-yellow-300' }}">
                        <x-dynamic-component :component="$action['component']" class="h-4 w-4" stroke-width="2.4" />
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach

            <div class="relative z-50" data-settings-menu>
                <button
                    type="button"
                    data-settings-menu-trigger
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-label="Configurações"
                    class="grid h-10 w-10 place-items-center rounded-full border border-yellow-300/25 bg-yellow-300 text-black transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-300/20 {{ $isSettingsRoute ? 'ring-4 ring-yellow-300/20' : '' }}"
                >
                    <x-tabler-settings class="h-5 w-5" stroke-width="2.4" />
                </button>

                <div data-settings-menu-panel class="absolute right-0 top-[calc(100%+0.5rem)] z-[120] hidden min-w-[240px] overflow-hidden rounded-2xl border border-yellow-300/20 bg-[#101010] p-2 shadow-2xl shadow-black/60" role="menu" aria-label="Configurações da loja">
                    <p class="px-3 py-2 font-orbitron text-[10px] font-black uppercase tracking-[.24em] text-zinc-500">Configurações</p>
                    @foreach ($settingsLinks as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            role="menuitem"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black transition focus:outline-none focus:ring-2 focus:ring-yellow-300 {{ request()->routeIs($link['route']) ? 'bg-yellow-300 text-black' : 'text-zinc-200 hover:bg-white/10 hover:text-yellow-300' }}"
                        >
                            <x-dynamic-component :component="$link['component']" class="h-4 w-4 shrink-0" stroke-width="2.2" />
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</header>

@if (! defined('garageon_cockpit_modals'))
    @php(define('garageon_cockpit_modals', true))
    @include('garageon.dashboard.sale-modal')
    @include('garageon.dashboard.quote-modal')
@endif
