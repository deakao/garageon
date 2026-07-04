<div class="grid gap-3 lg:grid-cols-[100px_1fr]">
    @foreach ($workHours as $hour)
        @php
            $slotTime = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
            $slotAppointments = $dayAppointments->filter(fn ($appointment) => (int) $appointment->scheduled_at->format('H') >= $hour && (int) $appointment->scheduled_at->format('H') < $hour + 2);
        @endphp
        <div class="pt-4 text-sm font-bold text-zinc-500">{{ $slotTime }}</div>
        <button
            type="button"
            data-calendar-slot
            data-calendar-day="{{ $dayDate->toDateString() }}"
            data-calendar-time="{{ $slotTime }}"
            class="min-h-20 rounded-2xl border border-white/10 bg-white/[.04] p-3 text-left transition hover:border-yellow-300/40 hover:bg-yellow-300/[.06] focus:outline-none focus:ring-2 focus:ring-yellow-300 cursor-pointer"
            aria-label="Agendar em {{ $dayDate->format('d/m') }} às {{ $slotTime }}"
        >
            @forelse ($slotAppointments as $appointment)
                <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source.' '.$appointment->vehicle?->plate.' '.$appointment->vehicle?->brand.' '.$appointment->vehicle?->model) }}" class="rounded-2xl border border-yellow-300/30 bg-yellow-300/15 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <strong class="font-orbitron text-sm text-yellow-300">{{ $appointment->scheduled_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}</strong>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-zinc-300">{{ $appointment->source }}</span>
                    </div>
                    <p class="mt-2 font-black text-white">{{ $appointment->service->name }}</p>
                    <p class="text-sm text-zinc-400">{{ $appointment->customer->name }}@if($appointment->vehicle) · {{ $appointment->vehicle->plate }} {{ $appointment->vehicle->brand }} {{ $appointment->vehicle->model }}@endif</p>
                </div>
            @empty
                <span class="block text-sm text-zinc-500">Horário livre para encaixe ou venda rápida.</span>
            @endforelse
        </button>
    @endforeach
</div>
