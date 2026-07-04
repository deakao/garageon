@php
    $quoteStatuses = [
        'sent' => 'Enviado',
        'pending' => 'Aguardando aprovação',
        'accepted' => 'Aceito',
        'expired' => 'Expirado',
    ];
    $serviceRows = old('services', $quote->items->map(fn ($item) => [
        'service_id' => $item->service_id,
        'quantity' => $item->quantity,
    ])->all() ?: [['service_id' => '', 'quantity' => 1]]);
    $quotedAt = $quote->quoted_at ?? $quote->created_at;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <label class="block sm:col-span-2">
        <span class="text-sm font-bold text-zinc-200">Status</span>
        <select name="status" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @foreach ($quoteStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $quote->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Data do orçamento</span>
        <input type="date" name="quoted_date" value="{{ old('quoted_date', $quotedAt->toDateString()) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('quoted_date') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Horário</span>
        <input type="time" name="quoted_time" value="{{ old('quoted_time', $quotedAt->format('H:i')) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('quoted_time') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block sm:col-span-2">
        <span class="text-sm font-bold text-zinc-200">Válido até</span>
        <input type="date" name="valid_until" value="{{ old('valid_until', $quote->valid_until?->toDateString()) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('valid_until') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>
</div>

<section data-vehicle-lookup data-vehicle-lookup-url="{{ route('vehicles.lookup') }}" class="mt-5 rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-4">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Veículo</p>
            <p class="mt-1 text-xs leading-5 text-zinc-400">Atualize placa e dados do carro vinculado ao orçamento.</p>
        </div>
        <p data-vehicle-lookup-status class="text-xs font-bold text-zinc-400" aria-live="polite"></p>
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        <label class="block flex-1">
            <span class="text-sm font-bold text-zinc-200">Placa</span>
            <input name="vehicle_plate" value="{{ old('vehicle_plate', $quote->vehicle?->plate) }}" required maxlength="10" data-vehicle-plate class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-orbitron text-sm font-black uppercase tracking-[.12em] text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="ABC1D23" autocomplete="off">
            @error('vehicle_plate') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
        <button type="button" data-vehicle-lookup-trigger class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-yellow-300/30 bg-yellow-300/10 px-5 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
            Buscar placa
        </button>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Marca</span>
            <input name="vehicle_brand" value="{{ old('vehicle_brand', $quote->vehicle?->brand) }}" required data-vehicle-brand class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @error('vehicle_brand') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Modelo</span>
            <input name="vehicle_model" value="{{ old('vehicle_model', $quote->vehicle?->model) }}" required data-vehicle-model class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @error('vehicle_model') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Ano</span>
            <input type="number" name="vehicle_year" value="{{ old('vehicle_year', $quote->vehicle?->year) }}" min="1900" max="{{ now()->addYear()->year }}" data-vehicle-year class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @error('vehicle_year') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Cor</span>
            <input name="vehicle_color" value="{{ old('vehicle_color', $quote->vehicle?->color) }}" data-vehicle-color class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @error('vehicle_color') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
    </div>
</section>

<div class="mt-5 grid gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Cliente</span>
        <input name="customer_name" value="{{ old('customer_name', $quote->customer->name) }}" required data-customer-name class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('customer_name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">WhatsApp</span>
        <input name="customer_phone" value="{{ old('customer_phone', $quote->customer->phone) }}" required data-customer-phone class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('customer_phone') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>
</div>

<section class="mt-5 rounded-3xl border border-white/10 bg-black/25 p-4" data-quote-service-editor>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Serviços</p>
            <p class="mt-1 text-xs text-zinc-400">Ajuste os itens e quantidades da proposta.</p>
        </div>
        <button type="button" data-quote-service-add class="inline-flex items-center justify-center rounded-2xl border border-yellow-300/30 px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
            Adicionar serviço
        </button>
    </div>

    <div class="mt-4 space-y-3" data-quote-service-list>
        @foreach ($serviceRows as $index => $row)
            <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/[.035] p-4 sm:grid-cols-[1fr_100px_auto] sm:items-end" data-quote-service-row>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Serviço</span>
                    <select name="services[{{ $index }}][service_id]" required data-quote-service-select class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                        <option value="">Escolha o serviço</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" @selected((int) ($row['service_id'] ?? 0) === $service->id)>{{ $service->name }} · R$ {{ number_format((float) $service->price, 2, ',', '.') }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Qtd.</span>
                    <input type="number" name="services[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" min="1" max="99" required data-quote-service-qty class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                </label>
                <button type="button" data-quote-service-remove class="rounded-xl border border-red-300/25 px-3 py-2.5 text-sm font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover serviço">Remover</button>
            </div>
        @endforeach
    </div>

    @error('services') <span class="mt-3 block text-xs text-red-300">{{ $message }}</span> @enderror

    <div class="mt-4 flex items-center justify-between rounded-2xl border border-yellow-300/20 bg-yellow-300/[.06] px-4 py-3">
        <span class="text-sm font-bold text-zinc-200">Total estimado</span>
        <strong data-quote-total class="font-orbitron text-xl font-black text-yellow-300">R$ 0,00</strong>
    </div>

    <template data-quote-service-template>
        <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/[.035] p-4 sm:grid-cols-[1fr_100px_auto] sm:items-end" data-quote-service-row>
            <label class="block">
                <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Serviço</span>
                <select name="services[__INDEX__][service_id]" required data-quote-service-select class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                    <option value="">Escolha o serviço</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" data-price="{{ $service->price }}">{{ $service->name }} · R$ {{ number_format((float) $service->price, 2, ',', '.') }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Qtd.</span>
                <input type="number" name="services[__INDEX__][quantity]" value="1" min="1" max="99" required data-quote-service-qty class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            </label>
            <button type="button" data-quote-service-remove class="rounded-xl border border-red-300/25 px-3 py-2.5 text-sm font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover serviço">Remover</button>
        </div>
    </template>
</section>

<label class="mt-5 block">
    <span class="text-sm font-bold text-zinc-200">Observações</span>
    <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Condições, prazos ou detalhes da proposta.">{{ old('notes', $quote->notes) }}</textarea>
    @error('notes') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
</label>
