<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamento #{{ str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT) }} - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    @php $shareUrl = $quote->publicUrl(); @endphp
    @php
        $whatsappMessage = "Olá {$quote->customer->name}! Segue o orçamento da {$tenant->name}: {$shareUrl}";
        $whatsappText = rawurlencode($whatsappMessage);
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.16),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.08),transparent_24%)] print:hidden"></div>

        <div class="relative mx-auto max-w-[1800px]">
            <div class="print:hidden">
                @include('garageon.dashboard.header')
            </div>

            @if (session('status'))
                <p class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100 print:hidden">{{ session('status') }}</p>
            @endif

            @error('whatsapp')
                <p class="mt-5 rounded-2xl border border-red-400/25 bg-red-400/10 px-5 py-4 text-sm font-bold text-red-100 print:hidden">{{ $message }}</p>
            @enderror

            @error('email')
                <p class="mt-5 rounded-2xl border border-red-400/25 bg-red-400/10 px-5 py-4 text-sm font-bold text-red-100 print:hidden">{{ $message }}</p>
            @enderror

            <section class="mt-6 flex flex-col gap-4 rounded-[24px] border border-white/10 bg-white/[.035] p-5 shadow-2xl shadow-black/20 backdrop-blur print:hidden">
                <div class="flex flex-col gap-1">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Compartilhar orçamento</p>
                    <p class="text-sm text-zinc-400">Envie o link para o cliente aprovar de qualquer dispositivo, gere o PDF ou imprima.</p>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <label class="flex flex-1 items-center gap-2 rounded-2xl border border-white/10 bg-black/40 px-4 py-3">
                        <x-tabler-link class="h-4 w-4 shrink-0 text-zinc-500" stroke-width="2.2" />
                        <input type="text" readonly value="{{ $shareUrl }}" data-share-url class="w-full bg-transparent text-sm text-zinc-200 outline-none" aria-label="Link do orçamento">
                    </label>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-copy-share="{{ $shareUrl }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-white/10 bg-black/30 px-4 py-3 text-sm font-black text-zinc-100 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            <x-tabler-copy class="h-4 w-4" stroke-width="2.2" />
                            <span data-copy-label>Copiar link</span>
                        </button>

                        @if ($whatsappConnected)
                            <form method="POST" action="{{ route('chat.messages.store') }}" class="inline-flex">
                                @csrf
                                <input type="hidden" name="customer_id" value="{{ $quote->customer_id }}">
                                <input type="hidden" name="body" value="{{ $whatsappMessage }}">
                                <input type="hidden" name="return_to" value="back">
                                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-black text-emerald-200 transition hover:bg-emerald-400 hover:text-black focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                    <x-tabler-brand-whatsapp class="h-4 w-4" stroke-width="2.2" />
                                    Enviar WhatsApp
                                </button>
                            </form>
                        @else
                            <a href="https://wa.me/?text={{ $whatsappText }}" target="_blank" rel="noopener" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-black text-emerald-200 transition hover:bg-emerald-400 hover:text-black focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                <x-tabler-brand-whatsapp class="h-4 w-4" stroke-width="2.2" />
                                Abrir WhatsApp
                            </a>
                        @endif

                        @if ($quote->customer->email)
                            <form method="POST" action="{{ route('quotes.email', $quote) }}" class="inline-flex">
                                @csrf
                                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-white/10 bg-black/30 px-4 py-3 text-sm font-black text-zinc-100 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                    <x-tabler-mail class="h-4 w-4" stroke-width="2.2" />
                                    Enviar e-mail
                                </button>
                            </form>
                        @else
                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm font-black text-zinc-500 opacity-70" title="Cadastre um e-mail no cliente para enviar por e-mail">
                                <x-tabler-mail class="h-4 w-4" stroke-width="2.2" />
                                E-mail não informado
                            </button>
                        @endif

                        <button type="button" data-print-quote class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-yellow-300 px-4 py-3 font-orbitron text-sm font-black uppercase tracking-[.12em] text-black transition hover:-translate-y-0.5 hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            <x-tabler-printer class="h-4 w-4" stroke-width="2.2" />
                            Imprimir / PDF
                        </button>
                    </div>
                </div>
            </section>

            <div class="mt-8 print:mt-0">
                @include('garageon.quotes.document')
            </div>

            <div class="mt-6 flex flex-wrap gap-3 print:hidden">
                <a href="{{ route('quotes.index') }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                    <x-tabler-list class="h-4 w-4" stroke-width="2.2" />
                    Todos os orçamentos
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                    <x-tabler-arrow-left class="h-4 w-4" stroke-width="2.2" />
                    Voltar ao cockpit
                </a>
            </div>
        </div>
    </main>
</body>
</html>
