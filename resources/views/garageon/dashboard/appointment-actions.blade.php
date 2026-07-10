@php
    $appointmentEditServices = $appointment->serviceItems->map(fn ($item) => [
        'service_id' => $item->service_id,
        'quantity' => $item->quantity,
    ])->values();

    if ($appointmentEditServices->isEmpty()) {
        $appointmentEditServices = collect([[
            'service_id' => $appointment->service_id,
            'quantity' => 1,
        ]]);
    }
@endphp

<div class="flex flex-wrap items-center gap-2">
    <button
        type="button"
        data-appointment-edit
        data-update-url="{{ route('appointments.update', $appointment) }}"
        data-customer-name="{{ $appointment->customer->name }}"
        data-customer-phone="{{ $appointment->customer->phone }}"
        data-vehicle-plate="{{ $appointment->vehicle?->plate }}"
        data-vehicle-brand="{{ $appointment->vehicle?->brand }}"
        data-vehicle-model="{{ $appointment->vehicle?->model }}"
        data-vehicle-year="{{ $appointment->vehicle?->year }}"
        data-vehicle-color="{{ $appointment->vehicle?->color }}"
        data-scheduled-date="{{ $appointment->scheduled_at->toDateString() }}"
        data-scheduled-time="{{ $appointment->scheduled_at->format('H:i') }}"
        data-status="{{ $appointment->status }}"
        data-notes="{{ $appointment->notes }}"
        data-services="{{ $appointmentEditServices->toJson() }}"
        class="inline-flex items-center justify-center rounded-full border border-yellow-300/30 px-3 py-1.5 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300 cursor-pointer"
    >
        Editar
    </button>

    <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" data-confirm="Excluir este agendamento da agenda?">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex items-center justify-center rounded-full border border-red-300/30 px-3 py-1.5 text-xs font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300 cursor-pointer">
            Excluir
        </button>
    </form>
</div>
