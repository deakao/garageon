<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Automações do funil - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    @php
        $stageStyles = [
            'sent' => 'border-yellow-300/30 bg-yellow-300/10 text-yellow-200',
            'pending' => 'border-amber-300/30 bg-amber-300/10 text-amber-200',
            'accepted' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200',
            'expired' => 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400',
        ];
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-[1800px]">
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
                <div class="grid divide-y divide-white/10 lg:grid-cols-3 lg:divide-x lg:divide-y-0 lg:divide-white/10">
                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-bolt class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Automações</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($stats['total'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">cadastradas no funil</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-circle-check class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Ativas</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($stats['active'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">prontas para disparar</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-brand-whatsapp class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">WhatsApp / E-mail</p>
                            <strong class="mt-1 block font-orbitron text-2xl font-black text-white">{{ $stats['whatsapp'] }} / {{ $stats['email'] }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">por canal</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="font-orbitron text-2xl font-black text-white">Automações do funil</h1>
                        <p class="mt-1 text-sm text-zinc-400">Crie quantas quiser por etapa. Defina canal, atraso (minutos, horas ou dias) e a mensagem.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('quotes.index') }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-white/10 px-5 py-3 text-sm font-black text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Ver funil
                        </a>
                        <button type="button" data-modal-open="automation-create-modal" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Nova automação
                        </button>
                    </div>
                </div>

                <div class="mb-6 rounded-3xl border border-white/10 bg-black/25 p-4">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-zinc-500">Variáveis disponíveis</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($placeholders as $token => $hint)
                            <span class="inline-flex items-center gap-2 rounded-full border border-yellow-300/20 bg-yellow-300/10 px-3 py-1.5 text-xs font-bold text-yellow-100" title="{{ $hint }}">
                                <code>{{ $token }}</code>
                            </span>
                        @endforeach
                    </div>
                </div>

                @if ($automations->isEmpty())
                    <div class="rounded-3xl border border-dashed border-white/10 bg-black/20 px-6 py-16 text-center">
                        <p class="font-orbitron text-sm font-black uppercase tracking-[.16em] text-zinc-500">Nenhuma automação ainda</p>
                        <p class="mt-2 text-sm text-zinc-400">Crie a primeira para enviar WhatsApp ou e-mail quando o orçamento mudar de etapa.</p>
                        <button type="button" data-modal-open="automation-create-modal" class="mt-4 inline-flex cursor-pointer items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Criar automação
                        </button>
                    </div>
                @else
                    <div class="overflow-hidden rounded-3xl border border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left">
                                <thead class="bg-black/45 text-[11px] font-black uppercase tracking-[.14em] text-zinc-500">
                                    <tr>
                                        <th class="px-4 py-3">Automação</th>
                                        <th class="px-4 py-3">Etapa</th>
                                        <th class="px-4 py-3">Canal</th>
                                        <th class="px-4 py-3">Atraso</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    @foreach ($automations as $automation)
                                        <tr class="bg-[#111]/80 hover:bg-white/[.04]">
                                            <td class="px-4 py-4">
                                                <strong class="block text-sm font-black text-white">{{ $automation->name }}</strong>
                                                <span class="mt-1 block max-w-md truncate text-xs text-zinc-500">{{ $automation->message_template }}</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[.1em] {{ $stageStyles[$automation->stage] ?? 'border-white/10 text-zinc-300' }}">
                                                    {{ $automation->stageLabel() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-sm font-bold text-zinc-200">{{ $automation->channelLabel() }}</td>
                                            <td class="px-4 py-4 text-sm font-bold text-zinc-300">{{ $automation->delayLabel() }}</td>
                                            <td class="px-4 py-4">
                                                @if ($automation->is_active)
                                                    <span class="inline-flex items-center gap-2 text-xs font-black text-emerald-300">
                                                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                                                        Ativa
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-2 text-xs font-black text-zinc-500">
                                                        <span class="h-2 w-2 rounded-full bg-zinc-500"></span>
                                                        Pausada
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <button type="button" data-modal-open="automation-edit-{{ $automation->id }}" class="cursor-pointer rounded-xl border border-yellow-300/30 px-3 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                                        Editar
                                                    </button>
                                                    <form method="POST" action="{{ route('settings.quote-funnel.destroy', $automation) }}" onsubmit="return confirm('Excluir esta automação?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="cursor-pointer rounded-xl border border-red-300/30 px-3 py-2 text-xs font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                            Excluir
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>

            <dialog id="automation-create-modal" class="customer-modal w-[min(94vw,720px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                <form method="POST" action="{{ route('settings.quote-funnel.store') }}" class="p-6 sm:p-8" data-automation-form>
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Nova automação</p>
                            <h2 class="mt-2 font-orbitron text-2xl font-black">Disparo do funil</h2>
                            <p class="mt-2 text-sm text-zinc-400">Escolha etapa, canal, atraso e a mensagem enviada ao cliente.</p>
                        </div>
                        <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                    </div>

                    <div class="mt-6 max-h-[62vh] overflow-y-auto pe-1">
                        @include('garageon.settings.quote-funnel-fields')
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-modal-close class="cursor-pointer rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                        <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Criar automação</button>
                    </div>
                </form>
            </dialog>

            @foreach ($automations as $automation)
                <dialog id="automation-edit-{{ $automation->id }}" class="customer-modal w-[min(94vw,720px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                    <form method="POST" action="{{ route('settings.quote-funnel.update', $automation) }}" class="p-6 sm:p-8" data-automation-form>
                        @csrf
                        @method('PUT')

                        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Editar automação</p>
                                <h2 class="mt-2 font-orbitron text-2xl font-black">{{ $automation->name }}</h2>
                            </div>
                            <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                        </div>

                        <div class="mt-6 max-h-[62vh] overflow-y-auto pe-1">
                            @include('garageon.settings.quote-funnel-fields', ['automation' => $automation])
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button type="button" data-modal-close class="cursor-pointer rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                            <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar</button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        </div>
    </main>
</body>
</html>
