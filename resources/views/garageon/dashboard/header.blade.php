<header class="rounded-[28px] border border-white/10 bg-white/[.035] p-5 shadow-2xl shadow-black/20 backdrop-blur sm:p-6">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-5">
        <img src="{{ $tenant->logoUrl() ?? asset('img/logo-horizontal.png') }}" alt="{{ $tenant->logoUrl() ? 'Logo da '.$tenant->name : 'GarageON' }}" class="h-9 max-w-56 object-contain sm:h-11">
        <div class="hidden items-center gap-3 text-right md:flex">
            <span class="rounded-full border border-yellow-300/25 px-4 py-2 font-orbitron text-xs font-black uppercase tracking-[.22em] text-yellow-300">Cockpit ativo</span>
            <span class="text-sm font-bold text-zinc-400">{{ $tenant->plan?->name ?? 'Trial GarageON' }}</span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(360px,1fr)_auto] xl:items-end">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Olá {{ $firstName }}, boa noite!</h1>
            <p class="mt-2 font-orbitron text-sm font-black text-yellow-300">{{ $tenant->name }} já está ON.</p>
            <p class="mt-2 text-sm text-zinc-400 sm:text-base">Hoje é dia {{ $today->format('d') }} de {{ $months[$today->month] }}, {{ ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][$today->dayOfWeek] }}.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            @foreach ($quickActions as $action)
                <a href="{{ $action['href'] }}" class="inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-black transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-yellow-300/20 {{ $action['primary'] ? 'bg-yellow-300 text-black shadow-lg shadow-yellow-300/15 hover:bg-white' : 'border border-white/10 bg-white/[.07] text-zinc-100 hover:border-yellow-300 hover:text-yellow-300' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $action['icon'] }}" /></svg>
                    {{ $action['label'] }}
                </a>
            @endforeach

            <a href="{{ route('settings.company') }}" class="grid h-12 w-12 place-items-center rounded-full border border-yellow-300/25 bg-yellow-300 text-black transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-300/20" aria-label="Configurações">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" /><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6l-.08.11a2 2 0 0 1-3.84 0L10 20a1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1l-.11-.08a2 2 0 0 1 0-3.84L4 10a1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6l.08-.11a2 2 0 0 1 3.84 0L14 4a1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.16.36.37.69.6 1l.11.08a2 2 0 0 1 0 3.84L20 14c-.23.31-.44.64-.6 1Z" /></svg>
            </a>

            <a href="{{ route('settings.landing') }}" class="grid h-12 w-12 place-items-center rounded-full border border-white/10 bg-white text-black transition hover:bg-yellow-300 focus:outline-none focus:ring-4 focus:ring-yellow-300/20" aria-label="Editar landing page da loja">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" /></svg>
            </a>
        </div>
    </div>
</header>
