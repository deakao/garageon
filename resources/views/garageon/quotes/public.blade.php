<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Orçamento #{{ str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT) }} - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    @php
        $shopWhatsapp = preg_replace('/\D/', '', (string) $tenant->whatsapp_phone);
        $whatsappText = rawurlencode("Olá! Quero aprovar o orçamento #".str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT).' da '.$tenant->name.'.');
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.14),transparent_24%),radial-gradient(circle_at_100%_8%,rgba(255,255,255,.07),transparent_22%)] print:hidden"></div>

        <div class="relative mx-auto max-w-5xl">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Proposta oficial · {{ $tenant->name }}</p>

                <div class="flex flex-wrap gap-2">
                    @if ($shopWhatsapp)
                        <a href="https://wa.me/55{{ $shopWhatsapp }}?text={{ $whatsappText }}" target="_blank" rel="noopener" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-2.5 text-sm font-black text-emerald-200 transition hover:bg-emerald-400 hover:text-black focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            <x-tabler-brand-whatsapp class="h-4 w-4" stroke-width="2.2" />
                            Aprovar no WhatsApp
                        </a>
                    @endif

                    <button type="button" data-print-quote class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-yellow-300 px-4 py-2.5 font-orbitron text-sm font-black uppercase tracking-[.12em] text-black transition hover:-translate-y-0.5 hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                        <x-tabler-printer class="h-4 w-4" stroke-width="2.2" />
                        Imprimir / PDF
                    </button>
                </div>
            </div>

            @include('garageon.quotes.document')

            <p class="mt-6 text-center text-xs text-zinc-600 print:hidden">Documento gerado por GarageON · {{ $tenant->name }}</p>
        </div>
    </main>
</body>
</html>
