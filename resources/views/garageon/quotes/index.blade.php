<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamentos - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    @php
        $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
        $statusLabels = [
            'sent' => 'Enviado',
            'pending' => 'Aguardando',
            'accepted' => 'Aceito',
            'expired' => 'Expirado',
        ];
        $statusStyles = [
            'sent' => 'border-yellow-300/30 text-yellow-200',
            'pending' => 'border-amber-300/30 text-amber-200',
            'accepted' => 'bg-yellow-300 text-black',
            'expired' => 'border-zinc-500/30 text-zinc-400',
        ];
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-7xl">
            @include('garageon.dashboard.header')

            @if (session('status'))
                <p class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-300/25 bg-red-300/10 px-5 py-4 text-sm text-red-100">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <section class="mt-6 overflow-hidden rounded-[32px] border border-white/10 bg-[#101010]/95 shadow-2xl shadow-black/30 backdrop-blur">
                <div class="grid divide-y divide-white/10 lg:grid-cols-4 lg:divide-x lg:divide-y-0 lg:divide-white/10">
                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-file-invoice class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Total de orçamentos</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($quoteStats['total'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">propostas ativas</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-send class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Enviados no mês</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($quoteStats['sent_this_month'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">novas propostas</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-currency-real class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Valor em aberto</p>
                            <strong class="mt-1 block font-orbitron text-2xl font-black text-white">{{ $money($quoteStats['pending_value']) }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">aguardando retorno</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-circle-check class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Aceitos</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($quoteStats['accepted'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">prontos para fechar</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="font-orbitron text-2xl font-black text-white">Orçamentos</h1>
                        <p class="mt-1 text-sm text-zinc-400">Gerencie propostas, acompanhe status e compartilhe com clientes.</p>
                    </div>
                    <button type="button" data-overlay-open="quote-modal" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                        Novo orçamento
                    </button>
                </div>

                <div class="quotes-datatable">
                    <table data-quotes-table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Veículo</th>
                                <th>Serviços</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Emitido</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($quotes as $quote)
                                <tr>
                                    <td>
                                        <strong class="font-orbitron text-sm font-black text-yellow-300">#{{ str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                    </td>
                                    <td>
                                        <strong class="block text-sm font-black text-white">{{ $quote->customer->name }}</strong>
                                        <span class="mt-1 block text-xs text-zinc-500">{{ $quote->customer->phone }}</span>
                                    </td>
                                    <td>
                                        @if ($quote->vehicle)
                                            <span class="block font-orbitron text-sm font-black text-zinc-100">{{ $quote->vehicle->plate }}</span>
                                            <span class="mt-1 block text-xs text-zinc-500">{{ trim($quote->vehicle->brand.' '.$quote->vehicle->model) }}</span>
                                        @else
                                            <span class="text-sm text-zinc-500">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-sm font-bold text-zinc-200">{{ $quote->items_count }} {{ $quote->items_count === 1 ? 'item' : 'itens' }}</span>
                                    </td>
                                    <td>
                                        <strong class="font-orbitron text-sm font-black text-yellow-300">{{ $money($quote->total) }}</strong>
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[.1em] {{ $statusStyles[$quote->status] ?? 'border border-white/10 text-zinc-300' }}">
                                            {{ $statusLabels[$quote->status] ?? str($quote->status)->headline() }}
                                        </span>
                                    </td>
                                    <td>{{ ($quote->quoted_at ?? $quote->created_at)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('quotes.show', $quote) }}" class="rounded-xl border border-white/10 px-3 py-2 text-xs font-black text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                                Ver
                                            </a>
                                            <button type="button" data-modal-open="quote-edit-{{ $quote->id }}" class="rounded-xl border border-yellow-300/30 px-3 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                                Editar
                                            </button>
                                            <form method="POST" action="{{ route('quotes.destroy', $quote) }}" onsubmit="return confirm('Excluir este orçamento? Essa ação não pode ser desfeita.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-xl border border-red-300/30 px-3 py-2 text-xs font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center">
                                        <p class="font-orbitron text-sm font-black uppercase tracking-[.16em] text-zinc-500">Nenhum orçamento ainda</p>
                                        <p class="mt-2 text-sm text-zinc-400">Monte a primeira proposta e compartilhe com seu cliente.</p>
                                        <button type="button" data-overlay-open="quote-modal" class="mt-4 inline-flex items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                            Criar orçamento
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @foreach ($quotes as $quote)
                <dialog id="quote-edit-{{ $quote->id }}" class="customer-modal w-[min(94vw,860px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                    <form method="POST" action="{{ route('quotes.update', $quote) }}" class="p-6 sm:p-8">
                        @csrf
                        @method('PUT')

                        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Editar orçamento</p>
                                <h2 class="mt-2 font-orbitron text-2xl font-black">#{{ str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT) }}</h2>
                                <p class="mt-2 text-sm text-zinc-400">Atualize status, serviços e dados do cliente.</p>
                            </div>
                            <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                        </div>

                        <div class="mt-6 max-h-[62vh] overflow-y-auto pe-1">
                            @include('garageon.quotes.form-fields', ['quote' => $quote, 'services' => $services])
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button type="button" data-modal-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                            <button type="submit" class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:-translate-y-0.5 hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar alterações</button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        </div>
    </main>
</body>
</html>
