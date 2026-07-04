@php
    $appointments = $customer->appointments;
    $quotes = $customer->quotes->reject(fn ($quote) => $quote->status === 'approved' || filled($quote->paid_at));
    $sales = $customer->quotes->filter(fn ($quote) => $quote->status === 'approved' || filled($quote->paid_at));

    $statusLabels = [
        'scheduled' => 'Agendado',
        'confirmed' => 'Confirmado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
        'sent' => 'Enviado',
        'pending' => 'Pendente',
        'accepted' => 'Aceito',
        'expired' => 'Expirado',
        'approved' => 'Venda',
    ];

    $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
@endphp

<div class="grid gap-4 lg:grid-cols-3">
    <article class="rounded-3xl border border-white/10 bg-black/30 p-5">
        <p class="text-xs font-black uppercase tracking-[.18em] text-zinc-500">Agenda</p>
        <strong class="mt-2 block font-orbitron text-3xl font-black text-white">{{ $appointments->count() }}</strong>
        <span class="mt-1 block text-sm text-zinc-400">visitas e retornos registrados</span>
    </article>
    <article class="rounded-3xl border border-white/10 bg-black/30 p-5">
        <p class="text-xs font-black uppercase tracking-[.18em] text-zinc-500">Orçamentos</p>
        <strong class="mt-2 block font-orbitron text-3xl font-black text-white">{{ $quotes->count() }}</strong>
        <span class="mt-1 block text-sm text-zinc-400">propostas ainda não vendidas</span>
    </article>
    <article class="rounded-3xl border border-yellow-300/20 bg-yellow-300/10 p-5">
        <p class="text-xs font-black uppercase tracking-[.18em] text-yellow-300">Vendas</p>
        <strong class="mt-2 block font-orbitron text-3xl font-black text-white">{{ $money($sales->sum('total')) }}</strong>
        <span class="mt-1 block text-sm text-yellow-100/80">{{ $sales->count() }} conversões no histórico</span>
    </article>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <section class="rounded-3xl border border-white/10 bg-black/25 p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-orbitron text-sm font-black uppercase tracking-[.16em] text-white">Agendamentos</h3>
            <span class="rounded-full border border-white/10 px-3 py-1 text-xs font-black text-zinc-400">{{ $appointments->count() }}</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($appointments as $appointment)
                <article class="rounded-2xl border border-white/10 bg-[#151515] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <strong class="block text-sm font-black text-white">{{ $appointment->service?->name ?? 'Serviço removido' }}</strong>
                            <span class="mt-1 block text-xs text-zinc-500">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-black uppercase tracking-[.08em] text-zinc-300">{{ $statusLabels[$appointment->status] ?? str($appointment->status)->headline() }}</span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-zinc-400">{{ $appointment->vehicle ? trim($appointment->vehicle->brand.' '.$appointment->vehicle->model).' · '.($appointment->vehicle->plate ?? 'sem placa') : 'Veículo não vinculado' }}</p>
                </article>
            @empty
                <p class="rounded-2xl border border-white/10 bg-black/30 p-4 text-sm leading-6 text-zinc-400">Nenhum agendamento ainda. Quando este cliente entrar na agenda, a linha do tempo aparece aqui.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-white/10 bg-black/25 p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-orbitron text-sm font-black uppercase tracking-[.16em] text-white">Orçamentos</h3>
            <span class="rounded-full border border-white/10 px-3 py-1 text-xs font-black text-zinc-400">{{ $quotes->count() }}</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($quotes as $quote)
                <article class="rounded-2xl border border-white/10 bg-[#151515] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('quotes.show', $quote) }}" class="block text-sm font-black text-white transition hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Orçamento #{{ $quote->id }}</a>
                            <span class="mt-1 block text-xs text-zinc-500">{{ ($quote->quoted_at ?? $quote->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <strong class="font-orbitron text-sm font-black text-yellow-300">{{ $money($quote->total) }}</strong>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-zinc-400">{{ $quote->items->pluck('name')->take(2)->join(' + ') ?: 'Itens não informados' }}</p>
                    <span class="mt-3 inline-flex rounded-full bg-white/10 px-3 py-1 text-[11px] font-black uppercase tracking-[.08em] text-zinc-300">{{ $statusLabels[$quote->status] ?? str($quote->status)->headline() }}</span>
                </article>
            @empty
                <p class="rounded-2xl border border-white/10 bg-black/30 p-4 text-sm leading-6 text-zinc-400">Nenhum orçamento aberto para este cliente. Uma nova proposta ficará visível aqui para follow-up.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-orbitron text-sm font-black uppercase tracking-[.16em] text-white">Vendas</h3>
            <span class="rounded-full border border-yellow-300/20 px-3 py-1 text-xs font-black text-yellow-300">{{ $sales->count() }}</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($sales as $sale)
                <article class="rounded-2xl border border-yellow-300/15 bg-[#151515] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('quotes.show', $sale) }}" class="block text-sm font-black text-white transition hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Venda #{{ $sale->id }}</a>
                            <span class="mt-1 block text-xs text-zinc-500">{{ ($sale->paid_at ?? $sale->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <strong class="font-orbitron text-sm font-black text-yellow-300">{{ $money($sale->total) }}</strong>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-zinc-400">{{ $sale->items->pluck('name')->take(2)->join(' + ') ?: 'Venda registrada no cockpit' }}</p>
                    @if ($sale->payment_method)
                        <span class="mt-3 inline-flex rounded-full bg-yellow-300/10 px-3 py-1 text-[11px] font-black uppercase tracking-[.08em] text-yellow-200">{{ str($sale->payment_method)->headline() }}</span>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-yellow-300/15 bg-black/30 p-4 text-sm leading-6 text-zinc-400">Ainda não há vendas para este cliente. Quando converter, o valor e os serviços aparecem nesta coluna.</p>
            @endforelse
        </div>
    </section>
</div>
