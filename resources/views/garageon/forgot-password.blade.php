<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha - GarageON</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B0B0B] text-white antialiased">
    <main class="relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_12%,rgba(255,196,0,.16),transparent_32%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_42%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:48px_48px]"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-yellow-300 to-transparent"></div>

        <section class="relative w-full max-w-md rounded-[24px] border border-white/10 bg-black/80 p-6 shadow-2xl shadow-black/70 backdrop-blur sm:p-8">
            <a href="{{ route('home') }}" class="mx-auto flex w-fit justify-center">
                <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-24 w-auto">
            </a>

            <div class="mt-8 text-center">
                <h1 class="font-orbitron text-2xl font-black tracking-tight text-white">Recuperar senha</h1>
                <p class="mt-2 text-sm leading-6 text-zinc-400">Informe seu e-mail para receber o link de redefinição.</p>
            </div>

            @if (session('status'))
                <p class="mt-6 rounded-xl border border-yellow-300/25 bg-yellow-300/10 px-4 py-3 text-sm leading-6 text-yellow-100">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="text-sm font-bold text-zinc-100">E-mail</label>
                    <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="contato@empresa.com" class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600">
                    </div>
                    @error('email')
                        <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-[14px] bg-yellow-300 px-6 py-4 font-orbitron text-sm font-black uppercase tracking-[.22em] text-black transition hover:-translate-y-0.5 hover:shadow-[0_0_34px_rgba(255,196,0,.28)] focus:outline-none focus:ring-4 focus:ring-yellow-300/30">
                    Enviar link
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm font-bold text-zinc-300 transition hover:text-yellow-200 focus:outline-none focus:ring-4 focus:ring-yellow-300/20">Voltar para entrar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
