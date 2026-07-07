<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Horários - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
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

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="border-b border-white/10 pb-5">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Horários de funcionamento</p>
                    <h2 class="mt-2 text-2xl font-black">Capacidade semanal</h2>
                    <p class="mt-2 text-sm text-zinc-400">Defina quando a agenda pode oferecer vagas para clientes e equipe.</p>
                </div>

                <form method="POST" action="{{ route('settings.hours.update') }}" class="mt-6 space-y-3">
                    @csrf
                    @method('PUT')

                    @foreach ([0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'] as $day => $label)
                        @php
                            $hour = $hours->get($day);
                            $isClosed = old("hours.$day.is_closed", $hour?->is_closed ?? $day === 0);
                        @endphp
                        <div class="grid gap-4 rounded-2xl border border-white/10 bg-black/35 p-4 md:grid-cols-[1fr_150px_150px_auto] md:items-center">
                            <strong class="font-orbitron text-sm text-white">{{ $label }}</strong>

                            <label class="block">
                                <span class="text-xs font-bold text-zinc-400">Abre</span>
                                <input type="time" name="hours[{{ $day }}][opens_at]" value="{{ old("hours.$day.opens_at", $hour?->opens_at ? substr($hour->opens_at, 0, 5) : '08:00') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-zinc-400">Fecha</span>
                                <input type="time" name="hours[{{ $day }}][closes_at]" value="{{ old("hours.$day.closes_at", $hour?->closes_at ? substr($hour->closes_at, 0, 5) : '18:00') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            </label>

                            <label class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[.04] px-3 py-2 text-sm font-bold text-zinc-200">
                                <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" @checked($isClosed) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
                                Fechado
                            </label>
                        </div>
                    @endforeach

                    <div class="flex justify-end border-t border-white/10 pt-5">
                        <button class="rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar horários</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
