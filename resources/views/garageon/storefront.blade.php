<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-zinc-950 antialiased">
    <main>
        <section class="relative overflow-hidden bg-zinc-950 px-6 py-8 text-white lg:px-10">
            <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(250,204,21,.24),transparent_34%),radial-gradient(circle_at_80%_20%,rgba(255,255,255,.13),transparent_30%)]"></div>
            <div class="relative mx-auto max-w-7xl">
                <nav class="flex items-center justify-between">
                    @if ($tenant->logoUrl())
                        <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="h-12 max-w-60 object-contain">
                    @else
                        <p class="font-orbitron text-xl font-black tracking-[.2em] text-yellow-300">{{ strtoupper($tenant->name) }}</p>
                    @endif
                    <a href="{{ route('booking', $tenant) }}" class="rounded-full bg-yellow-300 px-5 py-3 text-sm font-bold text-black">Agendar</a>
                </nav>

                <div class="grid gap-12 py-24 lg:grid-cols-[1.1fr_.9fr] lg:items-center">
                    <div>
                        <p class="mb-5 font-orbitron text-sm uppercase tracking-[.35em] text-yellow-300">Estética automotiva premium</p>
                        <h1 class="font-orbitron text-5xl font-black leading-none md:text-7xl">{{ $tenant->landingPage?->headline }}</h1>
                        <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-300">{{ $tenant->landingPage?->subheadline }}</p>
                        <a href="{{ route('booking', $tenant) }}" class="mt-9 inline-flex rounded-full bg-yellow-300 px-8 py-4 font-bold text-black shadow-[0_0_40px_rgba(250,204,21,.35)]">
                            {{ $tenant->landingPage?->cta_label ?? 'Agendar agora' }}
                        </a>
                    </div>
                    <div class="rounded-[2rem] border border-white/10 bg-white/[.07] p-5">
                        <div class="aspect-[4/5] rounded-[1.5rem] bg-[linear-gradient(145deg,#111,#000_45%,#facc15_46%,#facc15_52%,#111_53%)] p-6 shadow-2xl">
                            <div class="flex h-full flex-col justify-end rounded-[1.2rem] border border-white/10 bg-black/55 p-6 backdrop-blur">
                                <p class="font-orbitron text-3xl font-black text-yellow-300">DETAIL</p>
                                <p class="mt-2 text-sm text-zinc-300">Proteção, brilho e recorrência de manutenção.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-20 lg:px-10">
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($tenant->services as $service)
                    <article class="rounded-3xl border border-zinc-200 p-7 shadow-sm">
                        <p class="font-orbitron text-xl font-bold">{{ $service->name }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ $service->description }}</p>
                        <p class="mt-6 text-3xl font-black">R$ {{ number_format((float) $service->price, 2, ',', '.') }}</p>
                        <a href="{{ route('booking', $tenant) }}" class="mt-6 inline-flex rounded-full bg-zinc-950 px-5 py-3 text-sm font-bold text-yellow-300">Reservar</a>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bg-yellow-300 px-6 py-16 text-black lg:px-10">
            <div class="mx-auto grid max-w-7xl gap-6 md:grid-cols-2">
                @foreach ($tenant->landingPage?->sections ?? [] as $section)
                    <div class="rounded-3xl bg-black p-7 text-white">
                        <h2 class="font-orbitron text-2xl font-black text-yellow-300">{{ $section['title'] }}</h2>
                        <p class="mt-4 text-zinc-300">{{ $section['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
