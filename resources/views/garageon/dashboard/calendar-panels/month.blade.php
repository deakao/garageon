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
            $isToday = $date->isSameDay($today);
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
