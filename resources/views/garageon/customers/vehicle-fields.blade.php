@php
    $vehicleRows = $customer?->vehicles?->map(fn ($vehicle) => [
        'id' => $vehicle->id,
        'plate' => $vehicle->plate,
        'brand' => $vehicle->brand,
        'model' => $vehicle->model,
        'year' => $vehicle->year,
        'color' => $vehicle->color,
    ])->values()->all() ?: [[]];
@endphp

<div class="space-y-4" data-vehicle-editor>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="font-orbitron text-lg font-black text-white">Veículos do cliente</h3>
            <p class="mt-1 text-sm text-zinc-400">Cadastre placas e dados básicos para agilizar agenda e vendas.</p>
        </div>

        <button type="button" data-vehicle-add class="inline-flex items-center justify-center rounded-2xl border border-yellow-300/30 px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
            Adicionar veículo
        </button>
    </div>

    <div class="space-y-3" data-vehicle-list>
        @foreach ($vehicleRows as $index => $vehicle)
            <div class="rounded-2xl border border-white/10 bg-white/[.035] p-4" data-vehicle-row>
                <input type="hidden" name="vehicles[{{ $index }}][id]" value="{{ $vehicle['id'] ?? '' }}">

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="block lg:col-span-1">
                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Placa</span>
                        <input name="vehicles[{{ $index }}][plate]" value="{{ $vehicle['plate'] ?? '' }}" maxlength="10" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="ABC1D23">
                    </label>

                    <label class="block lg:col-span-1">
                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Marca</span>
                        <input name="vehicles[{{ $index }}][brand]" value="{{ $vehicle['brand'] ?? '' }}" maxlength="80" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Toyota">
                    </label>

                    <label class="block lg:col-span-1">
                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Modelo</span>
                        <input name="vehicles[{{ $index }}][model]" value="{{ $vehicle['model'] ?? '' }}" maxlength="120" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Corolla">
                    </label>

                    <label class="block lg:col-span-1">
                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Ano</span>
                        <input type="number" name="vehicles[{{ $index }}][year]" value="{{ $vehicle['year'] ?? '' }}" min="1900" max="{{ now()->addYear()->year }}" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="2024">
                    </label>

                    <div class="flex gap-2 lg:col-span-1">
                        <label class="block min-w-0 flex-1">
                            <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Cor</span>
                            <input name="vehicles[{{ $index }}][color]" value="{{ $vehicle['color'] ?? '' }}" maxlength="80" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Preto">
                        </label>

                        <button type="button" data-vehicle-remove class="mt-7 grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-red-300/25 text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover veículo">×</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template data-vehicle-template>
        <div class="rounded-2xl border border-white/10 bg-white/[.035] p-4" data-vehicle-row>
            <input type="hidden" name="vehicles[__INDEX__][id]" value="">

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="block lg:col-span-1"><span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Placa</span><input name="vehicles[__INDEX__][plate]" maxlength="10" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="ABC1D23"></label>
                <label class="block lg:col-span-1"><span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Marca</span><input name="vehicles[__INDEX__][brand]" maxlength="80" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Toyota"></label>
                <label class="block lg:col-span-1"><span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Modelo</span><input name="vehicles[__INDEX__][model]" maxlength="120" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Corolla"></label>
                <label class="block lg:col-span-1"><span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Ano</span><input type="number" name="vehicles[__INDEX__][year]" min="1900" max="{{ now()->addYear()->year }}" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="2024"></label>
                <div class="flex gap-2 lg:col-span-1"><label class="block min-w-0 flex-1"><span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Cor</span><input name="vehicles[__INDEX__][color]" maxlength="80" class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Preto"></label><button type="button" data-vehicle-remove class="mt-7 grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-red-300/25 text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover veículo">×</button></div>
            </div>
        </div>
    </template>
</div>
