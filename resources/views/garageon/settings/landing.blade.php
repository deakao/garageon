<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Landing Page - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    @php
        $sections = old('sections', $landingPage?->sections ?? [
            ['title' => 'Acabamento de showroom', 'body' => 'Processos padronizados e produtos profissionais.'],
            ['title' => 'Manutenção programada', 'body' => 'O GarageON chama o cliente de volta na hora certa.'],
        ]);
    @endphp

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>

        <div class="relative mx-auto max-w-6xl">
            @include('garageon.settings.nav')

            <section class="mt-6 grid gap-6 xl:grid-cols-[.85fr_1.15fr]">
                <article class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Landing page</p>
                    <h2 class="mt-2 text-2xl font-black">Página de venda da loja</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">Ajuste a promessa, CTA e blocos de valor que aparecem para clientes antes do agendamento.</p>

                    <div class="mt-6 rounded-2xl border border-white/10 bg-black/35 p-4">
                        <p class="text-sm font-bold text-zinc-200">Link público</p>
                        <a href="{{ route('storefront', $tenant) }}" target="_blank" class="mt-2 inline-flex text-sm font-black text-yellow-300 transition hover:text-yellow-100">
                            {{ route('storefront', $tenant) }}
                        </a>
                    </div>
                </article>

                <article class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                    <form method="POST" action="{{ route('settings.landing.update') }}" class="grid gap-5">
                        @csrf
                        @method('PUT')

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Headline principal</span>
                            <input name="headline" value="{{ old('headline', $landingPage?->headline ?? 'Seu carro tratado como máquina de pista') }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('headline') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Subtítulo</span>
                            <textarea name="subheadline" rows="3" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ old('subheadline', $landingPage?->subheadline ?? 'Lavagem técnica, vitrificação e proteção premium com agendamento online 24/7.') }}</textarea>
                            @error('subheadline') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Texto do botão principal</span>
                            <input name="cta_label" value="{{ old('cta_label', $landingPage?->cta_label ?? 'Agendar pelo WhatsApp') }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('cta_label') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <div class="grid gap-4">
                            @foreach ([0, 1] as $index)
                                <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                    <p class="font-orbitron text-xs font-black uppercase tracking-[.22em] text-yellow-300">Bloco {{ $index + 1 }}</p>

                                    <label class="mt-4 block">
                                        <span class="text-sm font-bold text-zinc-200">Título</span>
                                        <input name="sections[{{ $index }}][title]" value="{{ $sections[$index]['title'] ?? '' }}" required maxlength="120" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    </label>

                                    <label class="mt-4 block">
                                        <span class="text-sm font-bold text-zinc-200">Descrição</span>
                                        <textarea name="sections[{{ $index }}][body]" rows="2" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $sections[$index]['body'] ?? '' }}</textarea>
                                    </label>
                                </div>
                            @endforeach
                            @error('sections') <span class="block text-xs text-red-300">{{ $message }}</span> @enderror
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
                            <input type="checkbox" name="published" value="1" @checked(old('published', (bool) ($landingPage?->published_at))) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
                            Publicar landing page
                        </label>

                        <div class="flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <a href="{{ route('storefront', $tenant) }}" target="_blank" class="rounded-2xl border border-white/10 px-5 py-3 text-center text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Ver página</a>
                            <button class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar landing</button>
                        </div>
                    </form>
                </article>
            </section>
        </div>
    </main>
</body>
</html>
