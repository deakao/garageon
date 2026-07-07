<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atendente virtual - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-5xl">
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

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                    <div class="flex items-center gap-4">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-robot class="h-8 w-8" stroke-width="2.2" />
                        </div>
                        <div>
                            <h1 class="font-orbitron text-2xl font-black text-white">Piloto automático</h1>
                            <p class="mt-1 text-sm text-zinc-400">Seu atendente virtual que responde no WhatsApp e agenda serviços pela loja.</p>
                        </div>
                    </div>

                    @php
                        $operational = $attendant->exists && $attendant->is_active && filled($attendant->api_key);
                    @endphp
                    <span class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-black {{ $operational ? 'border-emerald-300/25 bg-emerald-300/10 text-emerald-200' : 'border-white/10 bg-white/[.04] text-zinc-300' }}">
                        <span class="h-2 w-2 rounded-full {{ $operational ? 'bg-emerald-300' : 'bg-zinc-500' }}"></span>
                        {{ $operational ? 'Atendendo' : 'Desligado' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('settings.attendant.update') }}" class="grid gap-5">
                    @csrf
                    @method('PUT')

                    @php
                        $remaining = max(0, $dailyLimit - $usedToday);
                        $usedPct = $dailyLimit > 0 ? min(100, round($usedToday / $dailyLimit * 100)) : 0;
                        $ownKey = $attendant->usesOwnKey();
                    @endphp
                    @if ($ownKey)
                        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-4">
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-emerald-200">Respostas ilimitadas</p>
                            <p class="mt-1 text-sm leading-6 text-zinc-300">Você está usando sua própria chave de IA, então não há limite diário de respostas. Os custos de uso são cobrados direto no seu provedor.</p>
                        </div>
                    @elseif ($requiresOwnKey)
                        <div class="rounded-2xl border border-yellow-300/25 bg-yellow-300/10 p-4">
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-200">Plano Autonomia: chave própria</p>
                            <p class="mt-1 text-sm leading-6 text-zinc-200">Seu plano oferece atendente <strong>ilimitado</strong>, mas você precisa informar sua própria API key de IA abaixo para ligar o atendimento. Assim você controla o custo dos tokens direto no seu provedor.</p>
                        </div>
                    @else
                        <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <div>
                                    <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Respostas de hoje</p>
                                    <p class="mt-1 text-sm text-zinc-400">Seu plano {{ $tenant->plan?->name ? '('.$tenant->plan->name.') ' : '' }}permite até <strong class="text-white">{{ number_format($dailyLimit, 0, ',', '.') }}</strong> respostas automáticas por dia. Quer respostas ilimitadas? Use sua própria chave abaixo.</p>
                                </div>
                                <p class="font-orbitron text-2xl font-black text-white">{{ number_format($usedToday, 0, ',', '.') }}<span class="text-base text-zinc-500">/{{ number_format($dailyLimit, 0, ',', '.') }}</span></p>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full {{ $usedPct >= 100 ? 'bg-red-400' : 'bg-yellow-300' }}" style="width: {{ $usedPct }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">
                                @if ($remaining > 0)
                                    Restam <strong class="text-zinc-300">{{ number_format($remaining, 0, ',', '.') }}</strong> respostas hoje. O contador zera à meia-noite.
                                @else
                                    Limite de hoje atingido. O atendente volta a responder automaticamente amanhã — enquanto isso, você pode responder pelo chat.
                                @endif
                            </p>
                        </div>
                    @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Nome do atendente</span>
                            <input name="name" value="{{ old('name', $attendant->name) }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Tom de voz</span>
                            <select name="tone" class="mt-2 w-full cursor-pointer rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @foreach ($toneOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('tone', $attendant->tone?->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tone') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Contexto da loja</span>
                        <textarea name="context" rows="5" maxlength="5000" placeholder="Ex.: Trabalhamos com vitrificação, polimento e higienização. Não atendemos motos. Aos sábados fechamos às 13h. Formas de pagamento: Pix e cartão em até 3x." class="mt-2 w-full resize-y rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ old('context', $attendant->context) }}</textarea>
                        <span class="mt-2 block text-xs leading-5 text-zinc-500">Esse texto complementa as instruções do atendente. Descreva serviços, regras e diferenciais da loja.</span>
                        @error('context') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Inteligência artificial</p>
                        <p class="mt-1 text-sm leading-6 text-zinc-400">Traga sua própria API key para respostas ilimitadas (custo cobrado no seu provedor) ou deixe em branco para usar a IA da plataforma dentro do limite do seu plano. A chave fica criptografada e nunca é exibida depois de salva.</p>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Provedor</span>
                                <select name="provider" class="mt-2 w-full cursor-pointer rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    @foreach ($providerOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('provider', $attendant->provider?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('provider') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Modelo <span class="font-normal text-zinc-500">(opcional)</span></span>
                                <input name="model" value="{{ old('model', $attendant->model) }}" maxlength="120" placeholder="Deixe vazio para usar o padrão do provedor" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('model') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">API key</span>
                                <input type="password" name="api_key" autocomplete="off" maxlength="255" placeholder="{{ filled($attendant->api_key) ? '•••••••••• (configurada — preencha só para trocar)' : 'Cole aqui a chave do provedor' }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('api_key') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-white/10 bg-black/35 p-4">
                        <span>
                            <span class="block text-sm font-black text-white">Confirmar agendamentos manualmente</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">Quando ligado, os agendamentos feitos pelo atendente ficam pendentes na agenda até você confirmar. Desligado, o horário é confirmado na hora.</span>
                        </span>
                        <input type="checkbox" name="require_booking_confirmation" value="1" @checked(old('require_booking_confirmation', $attendant->require_booking_confirmation)) class="h-6 w-6 shrink-0 cursor-pointer rounded-lg border-white/20 bg-black/40 text-yellow-300 focus:ring-2 focus:ring-yellow-300/40">
                    </label>

                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-white/10 bg-black/35 p-4">
                        <span>
                            <span class="block text-sm font-black text-white">Ligar atendimento automático</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">Quando ligado, o atendente responde sozinho as mensagens recebidas no WhatsApp.</span>
                        </span>
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $attendant->is_active)) class="h-6 w-6 shrink-0 cursor-pointer rounded-lg border-white/20 bg-black/40 text-yellow-300 focus:ring-2 focus:ring-yellow-300/40">
                    </label>

                    <details class="rounded-2xl border border-white/10 bg-black/35 p-4">
                        <summary class="cursor-pointer text-sm font-black text-zinc-200">Ver instruções geradas para o atendente</summary>
                        <pre class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap rounded-xl border border-white/10 bg-black/50 p-4 text-xs leading-6 text-zinc-300">{{ $promptPreview }}</pre>
                        <p class="mt-2 text-xs text-zinc-500">Prévia com base nas opções salvas. Alterações aparecem aqui após salvar.</p>
                    </details>

                    <div class="flex justify-end border-t border-white/10 pt-5">
                        <button class="rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar atendente</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
