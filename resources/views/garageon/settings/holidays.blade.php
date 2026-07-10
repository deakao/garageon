<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feriados - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
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

            <section class="mt-8 grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
                <article class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Bloqueio de agenda</p>
                    <h2 class="mt-2 text-2xl font-black">Novo feriado</h2>
                    <p class="mt-2 text-sm text-zinc-400">Dias cadastrados podem ser usados para impedir novas reservas automáticas.</p>

                    <form method="POST" action="{{ route('settings.holidays.store') }}" class="mt-6 grid gap-4">
                        @csrf

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Nome</span>
                            <input name="name" value="{{ old('name') }}" required placeholder="Ex: Natal, Recesso da equipe" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Data</span>
                            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('date') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
                            <input type="checkbox" name="repeats_yearly" value="1" @checked(old('repeats_yearly')) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
                            Repete todo ano
                        </label>

                        <div class="flex justify-end border-t border-white/10 pt-4">
                            <button class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Bloquear data</button>
                        </div>
                    </form>
                </article>

                <article class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                    <div class="border-b border-white/10 pb-5">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Datas bloqueadas</p>
                        <h2 class="mt-2 text-2xl font-black">{{ $holidays->count() }} feriados</h2>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($holidays as $holiday)
                            <div class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-black/35 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <strong class="font-orbitron text-sm text-white">{{ $holiday->date->format('d/m/Y') }} · {{ $holiday->name }}</strong>
                                    <span class="mt-1 block text-xs text-zinc-400">{{ $holiday->repeats_yearly ? 'Repete todo ano' : 'Somente nesta data' }}</span>
                                </div>

                                <form method="POST" action="{{ route('settings.holidays.destroy', $holiday) }}" onsubmit="return confirm('Remover este feriado da agenda?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-full border border-red-300/30 px-4 py-2 text-sm font-bold text-red-200 transition hover:bg-red-300/10">Remover</button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/15 p-8 text-center text-zinc-400">Nenhum feriado bloqueado. A agenda seguirá os horários semanais.</div>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </main>
</body>
</html>
