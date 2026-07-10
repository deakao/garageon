<div class="grid gap-3 lg:grid-cols-[100px_1fr]">
    @foreach ($workHours as $hour)
        @php
            $slotTime = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
            $slotAppointments = $dayAppointments->filter(fn ($appointment) => (int) $appointment->scheduled_at->format('H') >= $hour && (int) $appointment->scheduled_at->format('H') < $hour + 2);
        @endphp
        <div class="pt-4 text-sm font-bold text-zinc-500">{{ $slotTime }}</div>
        <div
            class="min-h-20 rounded-2xl border border-white/10 bg-white/[.04] p-3 text-left"
        >
            @forelse ($slotAppointments as $appointment)
                @php($customerPoints = number_format((int) ($appointment->customer->loyalty_points ?? 0), 0, ',', '.'))
                @php($vehicleLabel = $appointment->vehicle ? ' · '.$appointment->vehicle->plate.' '.$appointment->vehicle->brand.' '.$appointment->vehicle->model : '')
                @php($serviceLabel = $appointment->serviceSummary())
                <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$serviceLabel.' '.$appointment->source.' '.$appointment->vehicle?->plate.' '.$appointment->vehicle?->brand.' '.$appointment->vehicle?->model) }}" class="rounded-2xl border border-yellow-300/30 bg-yellow-300/15 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <strong class="font-orbitron text-sm text-yellow-300">{{ $appointment->scheduled_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}</strong>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-zinc-300">{{ $appointment->source }}</span>
                            @include('garageon.dashboard.appointment-actions', ['appointment' => $appointment])
                        </div>
                    </div>
                    <p class="mt-2 font-black text-white">{{ $serviceLabel }}</p>
                    <p class="text-sm text-zinc-400">{{ $appointment->customer->name }} · {{ $customerPoints }} pts{{ $vehicleLabel }}</p>
                </div>
            @empty
                <button
                    type="button"
                    data-calendar-slot
                    data-calendar-day="{{ $dayDate->toDateString() }}"
                    data-calendar-time="{{ $slotTime }}"
                    class="block w-full rounded-xl border border-white/5 px-3 py-4 text-left text-sm text-zinc-500 transition hover:border-yellow-300/40 hover:bg-yellow-300/[.06] focus:outline-none focus:ring-2 focus:ring-yellow-300 cursor-pointer"
                    aria-label="Agendar em {{ $dayDate->format('d/m') }} às {{ $slotTime }}"
                >
                    Horário livre para encaixe ou venda rápida.
                </button>
            @endforelse
        </div>
    @endforeach
</div>
