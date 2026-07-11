@php
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($currentStep, $stepKeys, true);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Configuração inicial') - GarageON</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-3xl">
            <div class="flex flex-col items-center text-center">
                <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-20 w-auto sm:h-24">
                <p class="mt-6 font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Configuração inicial</p>
                <h1 class="mt-2 font-orbitron text-2xl font-black text-white sm:text-3xl">{{ $tenant->name }}</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-400">Em poucos passos sua loja fica pronta para agenda, WhatsApp e landing page.</p>
            </div>

            <nav aria-label="Progresso do onboarding" class="mt-8">
                <ol class="grid grid-cols-5 gap-2">
                    @foreach ($steps as $key => $label)
                        @php
                            $index = array_search($key, $stepKeys, true);
                            $done = $currentIndex !== false && $index < $currentIndex;
                            $active = $key === $currentStep;
                        @endphp
                        <li class="flex flex-col items-center gap-2">
                            <span @class([
                                'grid h-9 w-9 place-items-center rounded-full font-orbitron text-xs font-black',
                                'bg-yellow-300 text-black' => $active,
                                'bg-yellow-300/20 text-yellow-200 ring-1 ring-yellow-300/40' => $done,
                                'bg-white/5 text-zinc-500 ring-1 ring-white/10' => ! $active && ! $done,
                            ])>{{ $index + 1 }}</span>
                            <span @class([
                                'hidden text-center text-[10px] font-bold uppercase tracking-[.12em] sm:block',
                                'text-yellow-200' => $active || $done,
                                'text-zinc-500' => ! $active && ! $done,
                            ])>{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            </nav>

            @if (session('status'))
                <p class="mt-6 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-red-300/25 bg-red-300/10 px-5 py-4 text-sm text-red-100">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                @yield('content')
            </section>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-3">
                    @isset($previousStep)
                        @if ($previousStep)
                            <a href="{{ route('onboarding.show', ['step' => $previousStep]) }}" class="cursor-pointer rounded-2xl border border-white/10 bg-white/[.04] px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/20 hover:bg-white/[.08]">Voltar</a>
                        @endif
                    @endisset
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    @isset($currentStep)
                        <form method="POST" action="{{ route('onboarding.skip-step', ['step' => $currentStep]) }}">
                            @csrf
                            <button type="submit" class="cursor-pointer rounded-2xl border border-white/10 bg-transparent px-5 py-3 text-sm font-bold text-zinc-300 transition hover:border-white/20 hover:text-white">Pular esta etapa</button>
                        </form>
                    @endisset

                    <a href="{{ route('onboarding.skip') }}" class="cursor-pointer text-sm font-bold text-zinc-500 underline-offset-4 transition hover:text-yellow-200 hover:underline">Configurar depois</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
