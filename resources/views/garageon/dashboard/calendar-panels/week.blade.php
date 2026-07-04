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
        @php $slotTime = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00'; @endphp
        <div class="py-4 text-xs font-bold text-zinc-500">{{ $slotTime }}</div>
        @for ($index = 0; $index < 7; $index++)
            @php
                $weekDate = $weekStart->copy()->addDays($index);
                $slotAppointments = $appointmentsByDate->get($weekDate->toDateString(), collect())->filter(fn ($appointment) => (int) $appointment->scheduled_at->format('H') >= $hour && (int) $appointment->scheduled_at->format('H') < $hour + 2);
            @endphp
            <button
                type="button"
                data-calendar-slot
                data-calendar-day="{{ $weekDate->toDateString() }}"
                data-calendar-time="{{ $slotTime }}"
                class="min-h-16 rounded-2xl border border-white/5 bg-white/[.035] p-2 text-left transition hover:border-yellow-300/40 hover:bg-yellow-300/[.06] focus:outline-none focus:ring-2 focus:ring-yellow-300 cursor-pointer"
                aria-label="Agendar em {{ $weekDate->format('d/m') }} às {{ $slotTime }}"
            >
                @forelse ($slotAppointments as $appointment)
                    <div data-calendar-event data-calendar-text="{{ \Illuminate\Support\Str::lower($appointment->customer->name.' '.$appointment->service->name.' '.$appointment->source.' '.$appointment->vehicle?->plate.' '.$appointment->vehicle?->brand.' '.$appointment->vehicle?->model) }}" class="rounded-xl bg-yellow-300 px-2 py-1 text-[11px] font-black leading-4 text-black">
                        {{ $appointment->scheduled_at->format('H:i') }} {{ $appointment->service->name }}
                    </div>
                @empty
                    <span class="block text-[11px] text-zinc-500">Horário livre</span>
                @endforelse
            </button>
        @endfor
    @endforeach
</div>
