@php
    $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
    $statusLabels = [
        'sent' => 'Enviado',
        'pending' => 'Aguardando aprovação',
        'approved' => 'Aprovado',
        'accepted' => 'Aceito',
        'expired' => 'Expirado',
    ];
    $statusLabel = $statusLabels[$quote->status] ?? str($quote->status)->headline();
    $isPositive = in_array($quote->status, ['approved', 'accepted'], true);
    $emittedAt = $quote->quoted_at ?? $quote->created_at;
    $logo = $tenant->logoUrl();
    $itemsCount = $quote->items->sum('quantity');
    $companyContacts = collect([
        $tenant->whatsapp_phone,
        $tenant->primary_domain,
    ])->filter()->all();
@endphp

<article class="quote-sheet mx-auto w-full overflow-hidden rounded-2xl bg-white text-zinc-900 shadow-2xl shadow-black/50 ring-1 ring-black/5 print:rounded-none print:shadow-none print:ring-0">
    <header class="quote-band relative overflow-hidden bg-[#0B0B0B] px-8 py-8 text-white print:px-10">
        <div class="pointer-events-none absolute inset-y-0 right-0 w-1/3 bg-[radial-gradient(circle_at_100%_0%,rgba(255,196,0,.35),transparent_60%)]"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $tenant->name }}" class="h-14 w-auto max-w-[180px] object-contain">
                @else
                    <div class="grid h-14 w-14 place-items-center rounded-xl border border-yellow-300/40 bg-yellow-300/10 font-orbitron text-2xl font-black text-yellow-300">
                        {{ str($tenant->name)->substr(0, 1)->upper() }}
                    </div>
                @endif
                <div>
                    <p class="font-orbitron text-lg font-black leading-tight text-white">{{ $tenant->legal_name ?: $tenant->name }}</p>
                    @if ($tenant->legal_name && $tenant->legal_name !== $tenant->name)
                        <p class="text-sm text-zinc-400">{{ $tenant->name }}</p>
                    @endif
                    @if ($tenant->document)
                        <p class="mt-1 text-xs text-zinc-500">CNPJ/CPF: {{ $tenant->document }}</p>
                    @endif
                    @foreach ($companyContacts as $contact)
                        <p class="text-xs text-zinc-400">{{ $contact }}</p>
                    @endforeach
                </div>
            </div>

            <div class="text-left sm:text-right">
                <p class="font-orbitron text-xs font-black uppercase tracking-[.32em] text-yellow-300">Orçamento</p>
                <p class="mt-1 font-orbitron text-3xl font-black leading-none text-white">#{{ str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT) }}</p>
                <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[.12em] {{ $isPositive ? 'bg-yellow-300 text-black' : 'border border-yellow-300/40 text-yellow-200' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isPositive ? 'bg-black' : 'bg-yellow-300' }}"></span>
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        <div class="relative mt-6 grid grid-cols-2 gap-4 border-t border-white/10 pt-5 text-xs sm:grid-cols-3">
            <div>
                <p class="font-black uppercase tracking-[.16em] text-zinc-500">Emitido em</p>
                <p class="mt-1 text-sm font-bold text-white">{{ $emittedAt->format('d/m/Y') }}</p>
                <p class="text-xs text-zinc-400">{{ $emittedAt->format('H:i') }}</p>
            </div>
            <div>
                <p class="font-black uppercase tracking-[.16em] text-zinc-500">Válido até</p>
                <p class="mt-1 text-sm font-bold text-white">{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <p class="font-black uppercase tracking-[.16em] text-zinc-500">Itens</p>
                <p class="mt-1 text-sm font-bold text-white">{{ $itemsCount }} {{ $itemsCount === 1 ? 'serviço' : 'serviços' }}</p>
            </div>
        </div>
    </header>

    <div class="grid gap-px bg-zinc-200 sm:grid-cols-2">
        <section class="bg-white px-8 py-6 print:px-10">
            <p class="font-orbitron text-[11px] font-black uppercase tracking-[.22em] text-zinc-400">Cliente</p>
            <p class="mt-2 text-lg font-black text-zinc-900">{{ $quote->customer->name }}</p>
            <dl class="mt-2 space-y-1 text-sm text-zinc-600">
                @if ($quote->customer->phone)
                    <div class="flex gap-2"><dt class="w-20 shrink-0 text-zinc-400">WhatsApp</dt><dd class="font-semibold text-zinc-800">{{ $quote->customer->phone }}</dd></div>
                @endif
                @if ($quote->customer->email)
                    <div class="flex gap-2"><dt class="w-20 shrink-0 text-zinc-400">E-mail</dt><dd class="font-semibold text-zinc-800">{{ $quote->customer->email }}</dd></div>
                @endif
            </dl>
        </section>

        <section class="bg-white px-8 py-6 print:px-10">
            <p class="font-orbitron text-[11px] font-black uppercase tracking-[.22em] text-zinc-400">Veículo</p>
            @if ($quote->vehicle)
                <p class="mt-2 font-orbitron text-lg font-black tracking-wide text-zinc-900">{{ $quote->vehicle->plate }}</p>
                <dl class="mt-2 space-y-1 text-sm text-zinc-600">
                    <div class="flex gap-2"><dt class="w-20 shrink-0 text-zinc-400">Modelo</dt><dd class="font-semibold text-zinc-800">{{ trim($quote->vehicle->brand.' '.$quote->vehicle->model) ?: '—' }}</dd></div>
                    @if ($quote->vehicle->year || $quote->vehicle->color)
                        <div class="flex gap-2"><dt class="w-20 shrink-0 text-zinc-400">Detalhes</dt><dd class="font-semibold text-zinc-800">{{ collect([$quote->vehicle->year, $quote->vehicle->color])->filter()->join(' · ') }}</dd></div>
                    @endif
                </dl>
            @else
                <p class="mt-2 text-sm text-zinc-500">Veículo não informado</p>
            @endif
        </section>
    </div>

    <section class="px-8 py-6 print:px-10">
        <p class="font-orbitron text-[11px] font-black uppercase tracking-[.22em] text-zinc-400">Serviços</p>
        <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-zinc-900 text-[11px] font-black uppercase tracking-[.12em] text-zinc-300">
                        <th class="px-4 py-3">Descrição</th>
                        <th class="w-16 px-4 py-3 text-center">Qtd.</th>
                        <th class="w-28 px-4 py-3 text-right">Unitário</th>
                        <th class="w-28 px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quote->items as $item)
                        <tr class="border-t border-zinc-100 {{ $loop->even ? 'bg-zinc-50' : 'bg-white' }}">
                            <td class="px-4 py-3">
                                <span class="block font-bold text-zinc-900">{{ $item->name }}</span>
                                @if ($item->service?->duration_minutes)
                                    <span class="mt-0.5 block text-xs text-zinc-400">Duração estimada: {{ $item->service->duration_minutes }} min</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-zinc-700">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right text-zinc-600">{{ $money($item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-zinc-900">{{ $money($item->unit_price * $item->quantity) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex justify-end">
            <div class="w-full max-w-xs space-y-2">
                <div class="flex items-center justify-between text-sm text-zinc-500">
                    <span>Subtotal</span>
                    <span class="font-semibold text-zinc-700">{{ $money($quote->total) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-[#0B0B0B] px-4 py-3 text-white">
                    <span class="font-orbitron text-xs font-black uppercase tracking-[.16em] text-yellow-300">Total</span>
                    <span class="font-orbitron text-2xl font-black text-yellow-300">{{ $money($quote->total) }}</span>
                </div>
            </div>
        </div>
    </section>

    @if ($quote->notes)
        <section class="border-t border-zinc-100 px-8 py-5 print:px-10">
            <p class="font-orbitron text-[11px] font-black uppercase tracking-[.22em] text-zinc-400">Observações</p>
            <p class="mt-2 text-sm leading-6 text-zinc-700">{{ $quote->notes }}</p>
        </section>
    @endif

    <footer class="border-t border-zinc-100 bg-zinc-50 px-8 py-5 text-xs text-zinc-500 print:px-10">
        <p>Este orçamento é válido até {{ $quote->valid_until?->format('d/m/Y') ?? 'a data combinada' }} e está sujeito à disponibilidade de agenda. Valores podem variar conforme o estado do veículo.</p>
        <p class="mt-2 font-semibold text-zinc-600">{{ $tenant->legal_name ?: $tenant->name }}@if (! empty($companyContacts)) · {{ implode(' · ', $companyContacts) }}@endif</p>
    </footer>
</article>
