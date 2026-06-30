<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agendamento - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    <main class="mx-auto grid min-h-screen max-w-7xl gap-8 px-6 py-8 lg:grid-cols-[.9fr_1.1fr] lg:px-10">
        <section class="flex flex-col justify-between rounded-[2rem] border border-yellow-300/20 bg-[radial-gradient(circle_at_top,rgba(250,204,21,.22),transparent_40%),#111] p-8">
            <div>
                <a href="{{ route('home') }}" class="font-orbitron text-sm uppercase tracking-[.35em] text-yellow-300">GarageON Agenda</a>
                <h1 class="mt-8 font-orbitron text-5xl font-black leading-none">{{ $tenant->name }}</h1>
                <p class="mt-5 text-lg leading-8 text-zinc-300">Escolha um serviço e um horário. O mesmo fluxo pode ser publicado na bio do Instagram ou usado pelo chatbot de WhatsApp.</p>
            </div>
            <div class="mt-10 rounded-3xl bg-black/70 p-5">
                <p class="text-sm uppercase tracking-[.25em] text-yellow-200">Próximos horários</p>
                <div class="mt-4 space-y-3">
                    @forelse ($tenant->appointments->take(3) as $appointment)
                        <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                            <p class="font-bold">{{ $appointment->scheduled_at->format('d/m H:i') }} · {{ $appointment->service->name }}</p>
                            <p class="text-sm text-zinc-400">{{ $appointment->customer->name }} · {{ $appointment->source }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">Nenhum horário ocupado no momento.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] bg-white p-6 text-black shadow-2xl">
            <div class="rounded-[1.5rem] border border-zinc-200 p-5">
                <h2 class="font-orbitron text-3xl font-black">Auto-agendamento</h2>
                <p class="mt-2 text-zinc-600">Protótipo visual do formulário externo do cliente.</p>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    @foreach ($tenant->services as $service)
                        <label class="cursor-pointer rounded-2xl border border-zinc-200 p-5 transition hover:-translate-y-1 hover:border-yellow-400 hover:shadow-xl">
                            <input type="radio" name="service" class="sr-only">
                            <span class="font-orbitron text-lg font-bold">{{ $service->name }}</span>
                            <span class="mt-3 block text-sm leading-6 text-zinc-600">{{ $service->duration_minutes }} min · R$ {{ number_format((float) $service->price, 2, ',', '.') }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-8">
                    <p class="mb-3 font-bold">Horários sugeridos pela agenda</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach (['Amanhã 10:30', 'Quinta 14:00', 'Sábado 09:00'] as $slot)
                            <button class="rounded-full border border-zinc-300 px-4 py-3 text-sm font-bold hover:border-yellow-400 hover:bg-yellow-300">{{ $slot }}</button>
                        @endforeach
                    </div>
                </div>

                @if ($orderBumps->isNotEmpty())
                    <div class="mt-8 rounded-3xl border-2 border-dashed border-yellow-400 bg-yellow-50 p-5">
                        <p class="font-orbitron text-sm uppercase tracking-[.25em] text-zinc-900">Order bump inteligente</p>
                        @foreach ($orderBumps as $bump)
                            <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-xl font-black">{{ $bump->name }}</p>
                                    <p class="text-sm text-zinc-700">{{ $bump->description }}</p>
                                </div>
                                <button class="rounded-full bg-black px-5 py-3 font-bold text-yellow-300">Adicionar R$ {{ number_format((float) $bump->price, 2, ',', '.') }}</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button class="mt-8 w-full rounded-full bg-black px-6 py-4 font-orbitron font-black uppercase tracking-[.18em] text-yellow-300">
                    Confirmar pelo WhatsApp
                </button>
            </div>
        </section>
    </main>
</body>
</html>
