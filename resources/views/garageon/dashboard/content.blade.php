<section class="mt-8 grid gap-5 xl:grid-cols-[1.55fr_1fr] 2xl:grid-cols-[1.65fr_1.1fr_.9fr]">
    <article data-calendar-dashboard class="rounded-[32px] border border-yellow-300/20 bg-[#0d0d0d]/95 p-5 shadow-2xl shadow-black/40 backdrop-blur sm:p-6 2xl:min-h-[560px]">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Agenda inteligente</p>
                <h2 class="mt-2 font-orbitron text-2xl font-black text-white sm:text-3xl">{{ $months[$today->month] }} / {{ $today->year }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-calendar-today class="rounded-full border border-white/10 px-4 py-2 text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Hoje</button>
                <div class="flex rounded-full border border-white/10 bg-black/40 p-1" aria-label="Navegação visual do calendário">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-zinc-300 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Mês anterior">‹</button>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-zinc-300 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Próximo mês">›</button>
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
                <input id="calendar-search" data-calendar-search type="search" placeholder="Buscar cliente, serviço ou origem" class="w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white placeholder:text-zinc-500 outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $calendarAppointments->count() }}</strong><span class="text-zinc-400">no mês</span></div>
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $todayAgenda->count() }}</strong><span class="text-zinc-400">hoje</span></div>
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3"><strong class="block font-orbitron text-xl text-yellow-300">{{ $dashboardStats['today_open_appointments'] }}</strong><span class="text-zinc-400">ativas</span></div>
            </div>
        </div>

        <div data-calendar-panel="month" class="mt-5">
            <div class="grid grid-cols-7 gap-2 text-center text-xs font-bold text-zinc-400">
                @foreach ($weekdays as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach

                @for ($blank = 0; $blank < $firstWeekday; $blank++)
                    <span></span>
                @endfor

                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $monthStart->copy()->day($day);
                        $dayAppointments = $appointmentsByDate->get($date->toDateString(), collect());
                        $isToday = $day === $today->day;
                    @endphp
                    <button type="button" data-calendar-day="{{ $date->toDateString() }}" class="group min-h-20 rounded-2xl border p-2 text-left transition hover:-translate-y-0.5 hover:border-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300 sm:min-h-24 {{ $isToday ? 'border-yellow-300 bg-yellow-300 text-black shadow-lg shadow-yellow-300/20' : 'border-white/5 bg-white/[.08] text-zinc-100' }}">
                        <span class="font-orbitron text-sm font-black">{{ $day }}</span>
                        @if ($dayAppointments->isNotEmpty())
                            <span class="mt-2 block truncate rounded-full {{ $isToday ? 'bg-black/15 text-black' : 'bg-yellow-300/15 text-yellow-300' }} px-2 py-1 text-[11px] font-black">{{ $dayAppointments->count() }} agenda{{ $dayAppointments->count() > 1 ? 's' : '' }}</span>
                            <span class="mt-1 block truncate text-[11px] {{ $isToday ? 'text-black/70' : 'text-zinc-400' }}">{{ $dayAppointments->first()->service->name }}</span>
                        @endif
                    </button>
                @endfor
            </div>
        </div>

        <div data-calendar-panel="week" class="mt-5 hidden">
            <div class="grid gap-2 overflow-x-auto pb-2 [grid-template-columns:72px_repeat(7,minmax(118px,1fr))]">
                <span></span>
                @for ($index = 0; $index < 7; $index++)
                    @php $weekDate = $weekStart->copy()->addDays($index); @endphp
                    <div class="rounded-2xl border border-white/10 bg-white/[.06] p-3 text-center">
                        <span class="block text-xs text-zinc-400">{{ $weekdays[$weekDate->dayOfWeek] }}</span>
                        <strong class="font-orbitron text-lg text-white">{{ $weekDate->format('d') }}</strong>
                    </div>
                @endfor

                @foreach ($workHours as $hour)
                    <div class="py-4 text-xs font-bold text-zinc-500">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                    @for ($index = 0; $index < 7; $index++)
                        @php
                            $weekDate = $weekStart->copy()->addDays($index);
                            $slotAppointments = $appointmentsByDate->get($weekDate->toDateString(), collect())->filter(fn ($appointment) => (int) $appointment->scheduled_at->format('H') >= $hour && (int) $appointment->scheduled_at->format('H') < $hour + 2);
                        @endphp
                        <div class="min-h-16 rounded-2xl border border-white/5 bg-white/[.035] p-2">
                            @foreach ($slotAppointments as $appointment)
                                <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source) }}" class="rounded-xl bg-yellow-300 px-2 py-1 text-[11px] font-black leading-4 text-black">
                                    {{ $appointment->scheduled_at->format('H:i') }} {{ $appointment->service->name }}
                                </div>
                            @endforeach
                        </div>
                    @endfor
                @endforeach
            </div>
        </div>

        <div data-calendar-panel="day" class="mt-5 hidden">
            <div class="grid gap-3 lg:grid-cols-[100px_1fr]">
                @foreach ($workHours as $hour)
                    <div class="pt-4 text-sm font-bold text-zinc-500">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                    @php $slotAppointments = $todayAgenda->filter(fn ($appointment) => (int) $appointment->scheduled_at->format('H') >= $hour && (int) $appointment->scheduled_at->format('H') < $hour + 2); @endphp
                    <div class="min-h-20 rounded-2xl border border-white/10 bg-white/[.04] p-3">
                        @forelse ($slotAppointments as $appointment)
                            <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source) }}" class="rounded-2xl border border-yellow-300/30 bg-yellow-300/15 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <strong class="font-orbitron text-sm text-yellow-300">{{ $appointment->scheduled_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}</strong>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-zinc-300">{{ $appointment->source }}</span>
                                </div>
                                <p class="mt-2 font-black text-white">{{ $appointment->service->name }}</p>
                                <p class="text-sm text-zinc-400">{{ $appointment->customer->name }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Horário livre para encaixe ou venda rápida.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-5 grid gap-3 border-t border-white/10 pt-5 lg:grid-cols-[1fr_240px]">
            <div class="space-y-2" data-calendar-list>
                @forelse ($calendarAppointments->take(4) as $appointment)
                    @php($appointmentStatus = ['scheduled' => 'Agendado'][$appointment->status] ?? str($appointment->status)->headline())
                    <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source) }}" class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-black/35 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <strong class="font-orbitron text-sm text-white">{{ $appointment->scheduled_at->format('d/m H:i') }} · {{ $appointment->service->name }}</strong>
                            <span class="block text-xs text-zinc-400">{{ $appointment->customer->name }} · {{ $appointment->source }}</span>
                        </div>
                        <span class="rounded-full border border-yellow-300/25 px-3 py-1 text-xs font-black text-yellow-300">{{ $appointmentStatus }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-white/15 p-5 text-sm text-zinc-400">Sua agenda ainda está livre. Use o botão de novo agendamento para preencher os melhores horários.</div>
                @endforelse
            </div>
            <a href="{{ route('booking', $tenant) }}" class="grid place-items-center rounded-2xl bg-yellow-300 px-5 py-4 text-center font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-yellow-300/20 focus:outline-none focus:ring-2 focus:ring-yellow-300">Novo agendamento</a>
        </div>
    </article>

    <article class="rounded-[28px] border border-yellow-300/25 bg-[#0d0d0d]/90 p-5 shadow-2xl shadow-black/40">
        <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black text-white">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-yellow-300 text-black">$</span>
                Resumo das vendas
            </h2>
            <p class="text-sm font-black text-emerald-300">{{ $money($paidSales) }} em vendas lançadas esse mês ✓</p>
        </div>

        <div class="mt-5 flex flex-wrap gap-x-8 gap-y-3 text-sm text-zinc-300">
            @foreach ($payments as $payment)
                <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rotate-45 {{ $payment['color'] }}"></i>{{ $payment['name'] }}</span>
            @endforeach
        </div>

        <div class="mt-6 space-y-5">
            @for ($line = 0; $line < 6; $line++)
                <div class="relative border-b border-dashed border-zinc-600/70 pb-2">
                    <span class="text-sm font-bold text-zinc-500">R$ 0,00</span>
                </div>
            @endfor
        </div>

        <div class="mt-5 grid grid-cols-3 gap-3 text-center text-xs font-black sm:grid-cols-6">
            @foreach ($payments as $payment)
                <span class="{{ $payment['amount'] > 0 ? 'text-emerald-300' : 'text-zinc-500' }}">{{ $money($payment['amount']) }}</span>
            @endforeach
        </div>
    </article>

    <article class="rounded-[28px] border border-white/10 bg-white/[.06] p-5 shadow-2xl shadow-black/30 backdrop-blur">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black text-white">
                <svg class="h-6 w-6 text-yellow-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9m4 10V5m4 14v-7m4 7V8m4 11V3" /></svg>
                Resumo financeiro
            </h2>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-zinc-300">mês atual</span>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-5 text-center">
                <h3 class="font-orbitron text-base font-black">Entradas hoje</h3>
                <p class="mt-3 text-xs leading-5 text-zinc-300">Valor estimado dos agendamentos do dia</p>
                <div class="mx-auto mt-4 h-px w-16 bg-emerald-300"></div>
                <strong class="mt-4 block text-2xl font-black text-emerald-300">{{ $money($dashboardStats['today_appointments'] * 149) }}</strong>
            </div>
            <div class="rounded-2xl border border-red-300/20 bg-red-300/10 p-5 text-center">
                <h3 class="font-orbitron text-base font-black">Saídas hoje</h3>
                <p class="mt-3 text-xs leading-5 text-zinc-300">Custos operacionais ainda não integrados</p>
                <div class="mx-auto mt-4 h-px w-16 bg-red-300"></div>
                <strong class="mt-4 block text-2xl font-black text-red-300">{{ $money(0) }}</strong>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-yellow-300/15 bg-yellow-300/10 p-5">
            <p class="max-w-xs text-sm leading-6 text-zinc-200">Valor total dos orçamentos ativos no funil comercial</p>
            <strong class="mt-3 block font-orbitron text-2xl text-yellow-300">{{ $money($paidSales) }}</strong>
        </div>
    </article>
</section>

<section class="mt-5 grid gap-5 lg:grid-cols-2 2xl:grid-cols-4">
    @foreach ($miniCards as $card)
        <article class="relative overflow-hidden rounded-[24px] border border-white/10 bg-white/[.06] p-5 shadow-xl shadow-black/20">
            <div class="absolute inset-0 bg-gradient-to-br {{ $card['accent'] }} to-transparent"></div>
            <div class="relative">
                <h2 class="flex items-center gap-3 border-b border-white/10 pb-4 font-orbitron text-base font-black">
                    <svg class="h-6 w-6 text-yellow-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $card['icon'] }}" /></svg>
                    {{ $card['title'] }}
                </h2>
                <div class="mt-5 flex flex-wrap gap-6">
                    @foreach ($card['items'] as $item)
                        <div class="min-w-28">
                            <strong class="block font-orbitron text-3xl text-yellow-300">{{ $item[0] }}</strong>
                            <span class="mt-1 block text-sm leading-5 text-zinc-300">{{ $item[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    @endforeach
</section>

<section class="mt-5 grid gap-5 xl:grid-cols-[.85fr_1.3fr]">
    <article class="rounded-[28px] border border-white/10 bg-white/[.06] p-6 shadow-xl shadow-black/20">
        <div class="flex flex-col gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black"><span class="text-yellow-300">▰</span>Sua empresa</h2>
            <p class="text-sm text-zinc-300">Na GarageON desde {{ $tenant->created_at->format('d/m/Y') }}</p>
        </div>
        <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="grid h-28 w-28 shrink-0 place-items-center rounded-[24px] bg-yellow-300 p-4 text-center font-orbitron text-sm font-black leading-4 text-black shadow-lg shadow-yellow-300/20">{{ $tenant->name }}</div>
            <div>
                <strong class="block text-2xl font-black">{{ $tenant->document ?? 'Documento não informado' }}</strong>
                <span class="mt-2 block text-lg text-zinc-200">{{ $tenant->legal_name ?? $tenant->name }}</span>
                <span class="mt-3 inline-flex rounded-full border border-yellow-300/25 px-3 py-1 text-xs font-black uppercase tracking-[.18em] text-yellow-300">{{ $tenant->plan?->name ?? 'Trial GarageON' }}</span>
            </div>
        </div>
    </article>

    <article class="rounded-[28px] border border-white/10 bg-white/[.06] p-6 shadow-xl shadow-black/20">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="flex items-center gap-2 font-orbitron text-lg font-black"><span class="text-yellow-300">●●</span>Top 5 clientes</h2>
            <span class="font-orbitron text-lg font-black text-yellow-300">{{ $dashboardStats['top_customers']->count() }} ✓</span>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @forelse ($dashboardStats['top_customers'] as $customer)
                <div class="rounded-2xl border border-white/10 bg-[#111]/90 p-4 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-yellow-300 text-black">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0" /><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
                    </div>
                    <strong class="mt-4 block text-sm font-black text-white">{{ $customer->name }}</strong>
                    <span class="mt-1 block text-xs text-zinc-400">{{ $customer->appointments_count }} agenda(s) · {{ $customer->quotes_count }} orçamento(s)</span>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-white/15 p-8 text-center text-zinc-400">Nenhum cliente cadastrado ainda.</div>
            @endforelse
        </div>
    </article>
</section>
