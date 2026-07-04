{{-- KPIs: resultado primeiro --}}
<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($miniCards as $card)
        <article class="relative h-full overflow-hidden rounded-3xl border border-white/10 bg-white/[.05] p-5 shadow-xl shadow-black/20">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $card['accent'] }} to-transparent"></div>
            <div class="relative flex h-full flex-col">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-yellow-300/15 text-yellow-300">
                        <x-dynamic-component :component="$card['component']" class="h-5 w-5" stroke-width="2.2" />
                    </span>
                    <h2 class="font-orbitron text-xs font-black uppercase tracking-[.16em] text-zinc-200">{{ $card['title'] }}</h2>
                </div>
                <div class="mt-6 flex flex-1 flex-wrap items-end gap-x-6 gap-y-4">
                    @foreach ($card['items'] as $item)
                        <div>
                            <strong class="block font-orbitron text-3xl font-black text-yellow-300">{{ $item[0] }}</strong>
                            <span class="mt-1 block text-xs leading-5 text-zinc-400">{{ $item[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    @endforeach
</section>

{{-- Agenda: herói operacional --}}
<section class="mt-5">
    <article data-calendar-dashboard data-calendar-agenda-url="{{ route('dashboard.agenda') }}" data-calendar-initial-month="{{ $monthStart->toDateString() }}" data-calendar-initial-week="{{ $weekStart->toDateString() }}" data-calendar-initial-day="{{ $today->toDateString() }}" data-calendar-initial-title-month="{{ $calendarTitles['month'] }}" data-calendar-initial-title-week="{{ $calendarTitles['week'] }}" data-calendar-initial-title-day="{{ $calendarTitles['day'] }}" class="rounded-[32px] border border-yellow-300/20 bg-[#0d0d0d]/95 p-5 shadow-2xl shadow-black/40 backdrop-blur sm:p-6">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Agenda inteligente</p>
                <h2 data-calendar-title class="mt-2 font-orbitron text-2xl font-black text-white sm:text-3xl">{{ $calendarTitles['month'] }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-calendar-today class="rounded-full border border-white/10 px-4 py-2 text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Hoje</button>
                <div class="flex rounded-full border border-white/10 bg-black/40 p-1" aria-label="Navegação da agenda">
                    <button type="button" data-calendar-prev class="grid h-9 w-9 place-items-center rounded-full text-zinc-300 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Período anterior">‹</button>
                    <button type="button" data-calendar-next class="grid h-9 w-9 place-items-center rounded-full text-zinc-300 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Próximo período">›</button>
                </div>
                <div class="flex rounded-full border border-white/10 bg-black/40 p-1" role="tablist" aria-label="Visualização da agenda">
                    @foreach (['month' => 'Mês', 'week' => 'Semana', 'day' => 'Dia'] as $view => $label)
                        <button type="button" data-calendar-view-button="{{ $view }}" class="rounded-full px-3 py-2 text-xs font-black transition focus:outline-none focus:ring-2 focus:ring-yellow-300 {{ $view === 'month' ? 'bg-yellow-300 text-black' : 'text-zinc-300 hover:text-white' }}" aria-selected="{{ $view === 'month' ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[1fr_280px]">
            <div>
                <label class="sr-only" for="calendar-search">Buscar cliente ou serviço na agenda</label>
                <input id="calendar-search" data-calendar-search type="search" placeholder="Buscar cliente, serviço, placa ou origem" class="w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white placeholder:text-zinc-500 outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $calendarAppointments->count() }}</strong><span class="text-zinc-400">no mês</span></div>
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $todayAgenda->count() }}</strong><span class="text-zinc-400">hoje</span></div>
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $dashboardStats['today_open_appointments'] }}</strong><span class="text-zinc-400">ativas</span></div>
            </div>
        </div>

        <div data-calendar-panel="month" class="mt-5">
            @include('garageon.dashboard.calendar-panels.month', ['appointmentsByDate' => $appointmentsByDate])
        </div>

        <div data-calendar-panel="week" class="mt-5 hidden">
            @include('garageon.dashboard.calendar-panels.week', ['appointmentsByDate' => $appointmentsByDate])
        </div>

        <div data-calendar-panel="day" class="mt-5 hidden">
            @include('garageon.dashboard.calendar-panels.day', ['dayAppointments' => $todayAgenda, 'dayDate' => $today])
        </div>

        <div class="mt-5 grid gap-3 border-t border-white/10 pt-5 ">
            <div class="space-y-2" data-calendar-list>
                @forelse ($calendarAppointments->take(4) as $appointment)
                    @php($appointmentStatus = ['scheduled' => 'Agendado'][$appointment->status] ?? str($appointment->status)->headline())
                    <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source.' '.$appointment->vehicle?->plate.' '.$appointment->vehicle?->brand.' '.$appointment->vehicle?->model) }}" class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-black/35 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <strong class="font-orbitron text-sm text-white">{{ $appointment->scheduled_at->format('d/m H:i') }} · {{ $appointment->service->name }}</strong>
                            <span class="block text-xs text-zinc-400">{{ $appointment->customer->name }}@if($appointment->vehicle) · {{ $appointment->vehicle->plate }} {{ $appointment->vehicle->brand }} {{ $appointment->vehicle->model }}@endif · {{ $appointment->source }}</span>
                        </div>
                        <span class="rounded-full border border-yellow-300/25 px-3 py-1 text-xs font-black text-yellow-300">{{ $appointmentStatus }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/15 p-5 text-sm text-zinc-400">Sua agenda ainda está livre. Use o botão de novo agendamento para preencher os melhores horários.</div>
                @endforelse
            </div>
            
        </div>
    </article>
</section>

{{-- Vendas + financeiro (alturas equilibradas pelo grid) --}}
<section class="mt-5 grid gap-5 xl:grid-cols-3">
    <article class="flex h-full flex-col rounded-[28px] border border-yellow-300/25 bg-[#0d0d0d]/90 p-6 shadow-2xl shadow-black/40 xl:col-span-2">
        <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black text-white">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-yellow-300 text-black">$</span>
                Resumo das vendas
            </h2>
            <p class="text-sm font-black text-emerald-300">{{ $money($paidSales) }} esse mês ✓</p>
        </div>

        <div class="mt-6 grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($payments as $payment)
                <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[.04] px-4 py-3">
                    <span class="inline-flex items-center gap-2 text-sm text-zinc-300"><i class="h-2.5 w-2.5 rotate-45 {{ $payment['color'] }}"></i>{{ $payment['name'] }}</span>
                    <strong class="font-orbitron text-sm font-black {{ $payment['amount'] > 0 ? 'text-emerald-300' : 'text-zinc-500' }}">{{ $money($payment['amount']) }}</strong>
                </div>
            @endforeach
        </div>
    </article>

    <article class="flex h-full flex-col rounded-[28px] border border-white/10 bg-white/[.06] p-6 shadow-2xl shadow-black/30 backdrop-blur xl:col-span-1">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black text-white">
                <x-tabler-chart-bar class="h-6 w-6 text-yellow-300" stroke-width="2.4" />
                Resumo financeiro
            </h2>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-zinc-300">mês atual</span>
        </div>

        <div class="mt-5 grid flex-1 content-start gap-4">
            <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-5">
                <h3 class="font-orbitron text-sm font-black">Entradas hoje</h3>
                <p class="mt-2 text-xs leading-5 text-zinc-300">Estimado dos agendamentos do dia</p>
                <strong class="mt-3 block text-2xl font-black text-emerald-300">{{ $money($dashboardStats['today_appointments'] * 149) }}</strong>
            </div>
            <div class="rounded-2xl border border-red-300/20 bg-red-300/10 p-5">
                <h3 class="font-orbitron text-sm font-black">Saídas hoje</h3>
                <p class="mt-2 text-xs leading-5 text-zinc-300">Custos ainda não integrados</p>
                <strong class="mt-3 block text-2xl font-black text-red-300">{{ $money(0) }}</strong>
            </div>
            <div class="rounded-2xl border border-yellow-300/15 bg-yellow-300/10 p-5">
                <h3 class="font-orbitron text-sm font-black text-yellow-200">Funil comercial</h3>
                <p class="mt-2 text-xs leading-5 text-zinc-300">Orçamentos ativos no período</p>
                <strong class="mt-3 block font-orbitron text-2xl text-yellow-300">{{ $money($paidSales) }}</strong>
            </div>
        </div>
    </article>
</section>

{{-- Empresa + clientes (alturas equilibradas pelo grid) --}}
<section class="mt-5 grid gap-5 xl:grid-cols-3">
    <article class="flex h-full flex-col rounded-[28px] border border-white/10 bg-white/[.06] p-6 shadow-xl shadow-black/20 xl:col-span-1">
        <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black"><span class="text-yellow-300">▰</span>Sua empresa</h2>
        </div>
        <div class="mt-6 flex flex-1 flex-col items-center gap-5 text-center">
            <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-[24px] bg-yellow-300 p-3 text-center font-orbitron text-sm font-black leading-4 text-black shadow-lg shadow-yellow-300/20">
                @if ($tenant->logoUrl())
                    <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="max-h-full max-w-full object-contain">
                @else
                    {{ $tenant->name }}
                @endif
            </div>
            <div>
                <strong class="block text-xl font-black">{{ $tenant->legal_name ?? $tenant->name }}</strong>
                <span class="mt-1 block text-sm text-zinc-400">{{ $tenant->document ?? 'Documento não informado' }}</span>
                <span class="mt-3 inline-flex rounded-full border border-yellow-300/25 px-3 py-1 text-xs font-black uppercase tracking-[.18em] text-yellow-300">{{ $tenant->plan?->name ?? 'Trial GarageON' }}</span>
                <span class="mt-3 block text-xs text-zinc-500">Na GarageON desde {{ $tenant->created_at->format('d/m/Y') }}</span>
            </div>
        </div>
    </article>

    <article class="flex h-full flex-col rounded-[28px] border border-white/10 bg-white/[.06] p-6 shadow-xl shadow-black/20 xl:col-span-2">
        <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black"><span class="text-yellow-300">●●</span>Top clientes</h2>
            <a href="{{ route('customers.index') }}" class="inline-flex w-fit items-center justify-center rounded-full border border-yellow-300/30 px-4 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                Ver todos os clientes
            </a>
        </div>
        <div class="mt-5 grid flex-1 content-start gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($dashboardStats['top_customers'] as $customer)
                <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-[#111]/90 p-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-yellow-300 text-black">
                        <x-tabler-user class="h-6 w-6" stroke-width="2.2" />
                    </div>
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-black text-white">{{ $customer->name }}</strong>
                        <span class="mt-1 block text-xs text-zinc-400">{{ $customer->appointments_count }} agenda(s) · {{ $customer->quotes_count }} orçamento(s)</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full grid flex-1 place-items-center rounded-2xl border border-dashed border-white/15 p-8 text-center text-zinc-400">Nenhum cliente cadastrado ainda.</div>
            @endforelse
        </div>
    </article>
</section>
