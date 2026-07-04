<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    @php
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $daysInMonth = $monthStart->daysInMonth;
        $firstWeekday = $monthStart->dayOfWeek;
        $weekStart = $today->copy()->subDays($today->dayOfWeek);
        $weekEnd = $weekStart->copy()->addDays(6);
        $months = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $workHours = range(8, 18, 2);
        $calendarTitles = [
            'month' => $months[$monthStart->month].' / '.$monthStart->year,
            'week' => $weekStart->format('d/m').' a '.$weekEnd->format('d/m').' · '.$months[$weekEnd->month].' / '.$weekEnd->year,
            'day' => $today->format('d').' de '.$months[$today->month].' / '.$today->year,
        ];
        $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
        $paidSales = $dashboardStats['month_quotes_total'];
        $calendarAppointments = $dashboardStats['calendar_appointments'];
        $appointmentsByDate = $calendarAppointments->groupBy(fn ($appointment) => $appointment->scheduled_at->toDateString());
        $todayAgenda = $appointmentsByDate->get($today->toDateString(), collect());
        $firstName = explode(' ', trim(auth()->user()->name))[0] ?: auth()->user()->name;
        $quickActions = [
            ['label' => 'Dashboard', 'component' => 'tabler-layout-dashboard', 'href' => route('dashboard'), 'primary' => false],
            ['label' => 'Nova Venda', 'component' => 'tabler-plus', 'overlay' => 'sale-modal', 'primary' => true],
            ['label' => 'Novo Agendamento', 'component' => 'tabler-calendar-plus', 'overlay' => 'appointment-modal', 'primary' => false],
            ['label' => 'Orçamentos', 'component' => 'tabler-file-invoice', 'href' => route('quotes.index'), 'primary' => false],
            ['label' => 'Clientes', 'component' => 'tabler-users', 'href' => route('customers.index'), 'primary' => false],
        ];
        $paymentTotals = $dashboardStats['month_sales_by_payment'];
        $payments = [
            ['name' => 'Débito', 'color' => 'bg-yellow-300', 'amount' => $paymentTotals['debito']],
            ['name' => 'Crédito', 'color' => 'bg-emerald-400', 'amount' => $paymentTotals['credito']],
            ['name' => 'Pix', 'color' => 'bg-blue-400', 'amount' => $paymentTotals['pix']],
            ['name' => 'Dinheiro', 'color' => 'bg-red-400', 'amount' => $paymentTotals['dinheiro']],
            ['name' => 'Boleto', 'color' => 'bg-zinc-400', 'amount' => $paymentTotals['boleto']],
            ['name' => 'Transferências', 'color' => 'bg-white', 'amount' => $paymentTotals['transferencia']],
        ];
        $miniCards = [
            ['title' => 'Orçamentos esse mês', 'component' => 'tabler-file-invoice', 'items' => [[$dashboardStats['month_quotes_pending'], 'orçamentos pendentes'], [$dashboardStats['month_quotes_approved'], 'orçamentos aprovados']], 'accent' => 'from-yellow-300/20'],
            ['title' => 'Vagas no espaço hoje', 'component' => 'tabler-parking', 'items' => [[$dashboardStats['today_open_appointments'], 'vagas ocupadas'], [$dashboardStats['today_completed_appointments'], 'vagas concluídas']], 'accent' => 'from-emerald-300/20'],
            ['title' => 'Oportunidades para hoje', 'component' => 'tabler-star', 'items' => [[$tenant->quotes_count, 'contatos pendentes'], [$dashboardStats['today_appointments'], 'oportunidades realizadas']], 'accent' => 'from-red-300/20'],
            ['title' => 'Funcionários cadastrados', 'component' => 'tabler-users-group', 'items' => [[$tenant->users()->count(), 'funcionários cadastrados nessa empresa']], 'accent' => 'from-zinc-100/10'],
        ];
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.20),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.06] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-[1800px]">
            @if (session('status'))
                <p class="mb-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            @include('garageon.dashboard.header')
            @include('garageon.dashboard.content')
            @include('garageon.dashboard.footer')
        </div>
    </main>

    <div data-appointment-modal class="fixed inset-0 z-[130] hidden items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="appointment-modal-title">
        <button type="button" data-appointment-close class="absolute inset-0 bg-black/80 backdrop-blur-sm" aria-label="Fechar modal de agendamento"></button>

        <section class="modal-panel relative w-full max-w-2xl rounded-[28px] border border-yellow-300/25 bg-[#101010] text-white shadow-2xl shadow-black/60">
            <div class="modal-panel-scroll">
            <div class="p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Novo agendamento</p>
                    <h2 id="appointment-modal-title" class="mt-2 font-orbitron text-2xl font-black">Reservar horário</h2>
                    <p data-appointment-readable-date class="mt-2 text-sm text-zinc-400">Selecione um dia na agenda.</p>
                </div>
                <button type="button" data-appointment-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
            </div>

            <form method="POST" action="{{ route('appointments.store') }}" class="mt-6 grid gap-5">
                @csrf
                <input type="hidden" name="scheduled_date" data-appointment-date value="{{ old('scheduled_date', $today->toDateString()) }}">

                <section data-vehicle-lookup data-vehicle-lookup-url="{{ route('vehicles.lookup') }}" class="rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Buscar por placa</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-400">Digite a placa para localizar o cliente e o veículo na base ou consultar os dados automaticamente.</p>
                        </div>
                        <p data-vehicle-lookup-status class="text-xs font-bold text-zinc-400" aria-live="polite"></p>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label class="block flex-1">
                            <span class="text-sm font-bold text-zinc-200">Placa</span>
                            <input name="vehicle_plate" value="{{ old('vehicle_plate') }}" required maxlength="10" data-vehicle-plate class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-orbitron text-sm font-black uppercase tracking-[.12em] text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="ABC1D23" autocomplete="off">
                            @error('vehicle_plate') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                        <button type="button" data-vehicle-lookup-trigger class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-yellow-300/30 bg-yellow-300/10 px-5 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300 cursor-pointer">
                            Buscar placa
                        </button>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Marca</span>
                            <input name="vehicle_brand" value="{{ old('vehicle_brand') }}" required data-vehicle-brand class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Toyota">
                            @error('vehicle_brand') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Modelo</span>
                            <input name="vehicle_model" value="{{ old('vehicle_model') }}" required data-vehicle-model class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Corolla Cross">
                            @error('vehicle_model') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Ano</span>
                            <input type="number" name="vehicle_year" value="{{ old('vehicle_year') }}" min="1900" max="{{ now()->addYear()->year }}" data-vehicle-year class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="2024">
                            @error('vehicle_year') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Cor</span>
                            <input name="vehicle_color" value="{{ old('vehicle_color') }}" data-vehicle-color class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Preto">
                            @error('vehicle_color') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Cliente</span>
                        <input name="customer_name" value="{{ old('customer_name') }}" required data-customer-name class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Nome do cliente">
                        @error('customer_name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">WhatsApp</span>
                        <input name="customer_phone" value="{{ old('customer_phone') }}" required data-customer-phone class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="(11) 99999-9999">
                        @error('customer_phone') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-[1fr_160px]">
                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Serviço</span>
                        <select name="service_id" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            <option value="">Escolha o serviço</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected((int) old('service_id') === $service->id)>{{ $service->name }} · {{ $service->duration_minutes }} min</option>
                            @endforeach
                        </select>
                        @error('service_id') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Horário</span>
                        <input type="time" name="scheduled_time" value="{{ old('scheduled_time', '09:00') }}" required data-appointment-time class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                        @error('scheduled_time') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-bold text-zinc-200">Observações</span>
                    <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: cliente quer avaliação da pintura antes do serviço.">{{ old('notes') }}</textarea>
                    @error('notes') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                </label>

                @if ($services->isEmpty())
                    <p class="rounded-2xl border border-red-300/20 bg-red-300/10 px-4 py-3 text-sm text-red-100">Cadastre um serviço ativo antes de criar agendamentos.</p>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" data-appointment-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                    <button type="submit" @disabled($services->isEmpty()) class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-yellow-300/20 focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">Salvar agendamento</button>
                </div>
            </form>
            </div>
            </div>
        </section>
    </div>

    <script>
        const appointmentModal = document.querySelector('[data-appointment-modal]');
        const appointmentDateInput = document.querySelector('[data-appointment-date]');
        const appointmentTimeInput = document.querySelector('[data-appointment-time]');
        const appointmentReadableDate = document.querySelector('[data-appointment-readable-date]');
        const appointmentShouldOpen = @json($errors->any() && old('_form') !== 'sale');

        const formatAppointmentDate = (date) => {
            const parsed = new Date(`${date}T00:00:00`);

            return parsed.toLocaleDateString('pt-BR', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });
        };

        const openAppointmentModal = (date, time = null) => {
            if (! appointmentModal) {
                return;
            }

            appointmentDateInput.value = date;

            if (appointmentTimeInput && time) {
                appointmentTimeInput.value = time;
            }

            const timeLabel = time ? ` às ${time}` : '';
            appointmentReadableDate.textContent = `Novo horário para ${formatAppointmentDate(date)}${timeLabel}`;
            appointmentModal.classList.remove('hidden');
            appointmentModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            appointmentModal.querySelector('input[name="customer_name"]')?.focus();
        };

        if (appointmentDateInput?.value) {
            appointmentReadableDate.textContent = `Novo horário para ${formatAppointmentDate(appointmentDateInput.value)}`;
        }

        if (appointmentShouldOpen && appointmentDateInput?.value) {
            openAppointmentModal(appointmentDateInput.value);
        }

        document.querySelectorAll('[data-calendar-dashboard]').forEach((calendar) => {
            const panels = calendar.querySelectorAll('[data-calendar-panel]');
            const viewButtons = calendar.querySelectorAll('[data-calendar-view-button]');
            const search = calendar.querySelector('[data-calendar-search]');
            const todayButton = calendar.querySelector('[data-calendar-today]');
            const prevButton = calendar.querySelector('[data-calendar-prev]');
            const nextButton = calendar.querySelector('[data-calendar-next]');
            const titleEl = calendar.querySelector('[data-calendar-title]');
            const agendaUrl = calendar.dataset.calendarAgendaUrl;
            const todayIso = '{{ $today->toDateString() }}';

            let currentView = 'month';
            let isLoadingAgenda = false;
            const anchors = {
                month: calendar.dataset.calendarInitialMonth || todayIso,
                week: calendar.dataset.calendarInitialWeek || todayIso,
                day: calendar.dataset.calendarInitialDay || todayIso,
            };
            const titles = {
                month: calendar.dataset.calendarInitialTitleMonth || '',
                week: calendar.dataset.calendarInitialTitleWeek || '',
                day: calendar.dataset.calendarInitialTitleDay || '',
            };

            const activateView = (view) => {
                currentView = view;
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.calendarPanel !== view));
                viewButtons.forEach((button) => {
                    const active = button.dataset.calendarViewButton === view;
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                    button.classList.toggle('bg-yellow-300', active);
                    button.classList.toggle('text-black', active);
                    button.classList.toggle('text-zinc-300', ! active);
                    button.classList.toggle('hover:text-white', ! active);
                });

                if (titleEl && titles[view]) {
                    titleEl.textContent = titles[view];
                }
            };

            const shiftAnchor = (view, date, direction) => {
                const parsed = new Date(`${date}T00:00:00`);

                if (view === 'month') {
                    parsed.setMonth(parsed.getMonth() + direction);
                } else if (view === 'week') {
                    parsed.setDate(parsed.getDate() + direction * 7);
                } else {
                    parsed.setDate(parsed.getDate() + direction);
                }

                return parsed.toISOString().slice(0, 10);
            };

            const loadAgenda = async (view, date) => {
                if (! agendaUrl || isLoadingAgenda) {
                    return;
                }

                const panel = calendar.querySelector(`[data-calendar-panel="${view}"]`);

                if (! panel) {
                    return;
                }

                isLoadingAgenda = true;
                panel.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(`${agendaUrl}?view=${view}&date=${date}`, {
                        headers: { Accept: 'application/json' },
                    });

                    if (! response.ok) {
                        throw new Error('agenda_failed');
                    }

                    const payload = await response.json();
                    panel.innerHTML = payload.panel;

                    titles[view] = payload.title;

                    if (titleEl && view === currentView) {
                        titleEl.textContent = payload.title;
                    }

                    anchors[view] = payload.date;
                } catch (error) {
                    // mantém o conteúdo anterior visível; falha silenciosa evita travar a agenda
                } finally {
                    panel.removeAttribute('aria-busy');
                    isLoadingAgenda = false;
                }
            };

            viewButtons.forEach((button) => button.addEventListener('click', () => activateView(button.dataset.calendarViewButton)));

            prevButton?.addEventListener('click', () => {
                loadAgenda(currentView, shiftAnchor(currentView, anchors[currentView], -1));
            });

            nextButton?.addEventListener('click', () => {
                loadAgenda(currentView, shiftAnchor(currentView, anchors[currentView], 1));
            });

            todayButton?.addEventListener('click', () => {
                activateView('day');
                loadAgenda('day', todayIso);
            });

            calendar.addEventListener('click', (event) => {
                if (event.target.closest('[data-calendar-event]')) {
                    return;
                }

                const slotButton = event.target.closest('[data-calendar-slot]');

                if (slotButton) {
                    openAppointmentModal(slotButton.dataset.calendarDay, slotButton.dataset.calendarTime);

                    return;
                }

                const dayButton = event.target.closest('[data-calendar-day]');

                if (dayButton) {
                    openAppointmentModal(dayButton.dataset.calendarDay);
                }
            });

            search?.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                calendar.querySelectorAll('[data-calendar-event]').forEach((event) => {
                    event.classList.toggle('hidden', term.length > 0 && ! event.dataset.calendarText.includes(term));
                });
            });
        });
    </script>
</body>
</html>
