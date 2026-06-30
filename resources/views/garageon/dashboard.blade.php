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
        $daysInMonth = $today->daysInMonth;
        $firstWeekday = $monthStart->dayOfWeek;
        $weekStart = $today->copy()->subDays($today->dayOfWeek);
        $months = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $workHours = range(8, 18, 2);
        $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
        $paidSales = $dashboardStats['month_quotes_total'];
        $calendarAppointments = $dashboardStats['calendar_appointments'];
        $appointmentsByDate = $calendarAppointments->groupBy(fn ($appointment) => $appointment->scheduled_at->toDateString());
        $todayAgenda = $appointmentsByDate->get($today->toDateString(), collect());
        $firstName = explode(' ', trim(auth()->user()->name))[0] ?: auth()->user()->name;
        $quickActions = [
            ['label' => 'Nova Venda', 'icon' => 'M12 3v18m7-11H5', 'href' => route('booking', $tenant), 'primary' => true],
            ['label' => 'Novo Agendamento', 'icon' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z', 'href' => route('booking', $tenant), 'primary' => false],
            ['label' => 'Novo Orçamento', 'icon' => 'M8 4h8l4 4v12H8V4Zm8 0v5h5M11 13h6M11 17h4', 'href' => route('storefront', $tenant), 'primary' => false],
            ['label' => 'Preencher Vaga', 'icon' => 'M9 11l3 3L22 4M3 7h5M3 12h5M3 17h5', 'href' => route('booking', $tenant), 'primary' => false],
        ];
        $payments = [
            ['name' => 'Débito', 'color' => 'bg-yellow-300', 'amount' => 0],
            ['name' => 'Crédito', 'color' => 'bg-emerald-400', 'amount' => $paidSales],
            ['name' => 'Pix', 'color' => 'bg-blue-400', 'amount' => 0],
            ['name' => 'Dinheiro', 'color' => 'bg-red-400', 'amount' => 0],
            ['name' => 'Boleto', 'color' => 'bg-zinc-400', 'amount' => 0],
            ['name' => 'Transferências', 'color' => 'bg-white', 'amount' => 0],
        ];
        $miniCards = [
            ['title' => 'Orçamentos esse mês', 'icon' => 'M7 3h7l5 5v13H7V3Zm7 0v6h6M10 13h6M10 17h4', 'items' => [[$dashboardStats['month_quotes_pending'], 'orçamentos pendentes'], [$dashboardStats['month_quotes_approved'], 'orçamentos aprovados']], 'accent' => 'from-yellow-300/20'],
            ['title' => 'Vagas no espaço hoje', 'icon' => 'M5 4h4v4H5V4Zm6 0h4v4h-4V4Zm6 0h2v4h-2V4ZM5 11h4v9H5v-9Zm6 0h4v9h-4v-9Zm6 0h2v9h-2v-9Z', 'items' => [[$dashboardStats['today_open_appointments'], 'vagas ocupadas'], [$dashboardStats['today_completed_appointments'], 'vagas concluídas']], 'accent' => 'from-emerald-300/20'],
            ['title' => 'Oportunidades para hoje', 'icon' => 'M12 3l2.4 5 5.6.8-4 3.9 1 5.5L12 15.8 7 18.2l1-5.5-4-3.9 5.6-.8L12 3Z', 'items' => [[$tenant->quotes_count, 'contatos pendentes'], [$dashboardStats['today_appointments'], 'oportunidades realizadas']], 'accent' => 'from-red-300/20'],
            ['title' => 'Funcionários cadastrados', 'icon' => 'M16 11a4 4 0 1 0-8 0v1a4 4 0 0 0 8 0v-1Zm-9 9a5 5 0 0 1 10 0M19 8a3 3 0 0 1 0 6M22 20a4 4 0 0 0-3-3.87M5 8a3 3 0 0 0 0 6M2 20a4 4 0 0 1 3-3.87', 'items' => [[$tenant->users()->count(), 'funcionários cadastrados nessa empresa']], 'accent' => 'from-zinc-100/10'],
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

    <div data-appointment-modal class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="appointment-modal-title">
        <button type="button" data-appointment-close class="absolute inset-0 bg-black/80 backdrop-blur-sm" aria-label="Fechar modal de agendamento"></button>

        <section class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[28px] border border-yellow-300/25 bg-[#101010] p-6 text-white shadow-2xl shadow-black/60 sm:p-8">
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

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Cliente</span>
                        <input name="customer_name" value="{{ old('customer_name') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Nome do cliente">
                        @error('customer_name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">WhatsApp</span>
                        <input name="customer_phone" value="{{ old('customer_phone') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="(11) 99999-9999">
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
                        <input type="time" name="scheduled_time" value="{{ old('scheduled_time', '09:00') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
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
        </section>
    </div>

    <script>
        const appointmentModal = document.querySelector('[data-appointment-modal]');
        const appointmentDateInput = document.querySelector('[data-appointment-date]');
        const appointmentReadableDate = document.querySelector('[data-appointment-readable-date]');
        const appointmentShouldOpen = @json($errors->any());

        const formatAppointmentDate = (date) => {
            const parsed = new Date(`${date}T00:00:00`);

            return parsed.toLocaleDateString('pt-BR', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });
        };

        const openAppointmentModal = (date) => {
            if (! appointmentModal) {
                return;
            }

            appointmentDateInput.value = date;
            appointmentReadableDate.textContent = `Novo horário para ${formatAppointmentDate(date)}`;
            appointmentModal.classList.remove('hidden');
            appointmentModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            appointmentModal.querySelector('input[name="customer_name"]')?.focus();
        };

        const closeAppointmentModal = () => {
            appointmentModal?.classList.add('hidden');
            appointmentModal?.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        document.querySelectorAll('[data-appointment-close]').forEach((button) => button.addEventListener('click', closeAppointmentModal));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAppointmentModal();
            }
        });

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
            const today = '{{ $today->toDateString() }}';

            const activateView = (view) => {
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.calendarPanel !== view));
                viewButtons.forEach((button) => {
                    const active = button.dataset.calendarViewButton === view;
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                    button.classList.toggle('bg-yellow-300', active);
                    button.classList.toggle('text-black', active);
                    button.classList.toggle('text-zinc-300', ! active);
                    button.classList.toggle('hover:text-white', ! active);
                });
            };

            viewButtons.forEach((button) => button.addEventListener('click', () => activateView(button.dataset.calendarViewButton)));

            todayButton?.addEventListener('click', () => {
                activateView('day');
                calendar.querySelector(`[data-calendar-day="${today}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            calendar.querySelectorAll('[data-calendar-day]').forEach((day) => {
                day.addEventListener('click', () => openAppointmentModal(day.dataset.calendarDay));
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
