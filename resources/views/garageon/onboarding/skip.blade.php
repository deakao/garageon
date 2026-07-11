<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar depois - GarageON</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(250,204,21,.16),transparent_30%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-2xl">
            <div class="flex flex-col items-center text-center">
                <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-20 w-auto sm:h-24">
                <p class="mt-6 font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Sem pressa</p>
                <h1 class="mt-3 font-orbitron text-3xl font-black text-white">Tudo bem configurar depois</h1>
                <p class="mt-3 max-w-lg text-sm leading-6 text-zinc-400">Você pode entrar no dashboard agora. O que faltar fica disponível em Configurações quando quiser.</p>
            </div>

            <section class="mt-10 rounded-[32px] border border-white/10 bg-[#101010]/95 p-6 shadow-2xl shadow-black/30 sm:p-8">
                <h2 class="font-orbitron text-sm font-black uppercase tracking-[.2em] text-yellow-300">Status da configuração</h2>
                <p class="mt-2 text-sm text-zinc-400">{{ $pendingCount === 0 ? 'Tudo preenchido — você pode concluir com tranquilidade.' : $pendingCount.' item(ns) ainda pendente(s).' }}</p>

                <ul class="mt-6 space-y-3">
                    @foreach ($steps as $key => $label)
                        @php $done = $checklist[$key] ?? false; @endphp
                        <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/35 px-4 py-3">
                            <span class="font-bold text-white">{{ $label }}</span>
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-black uppercase tracking-[.14em]',
                                'bg-emerald-300/15 text-emerald-200' => $done,
                                'bg-white/5 text-zinc-400' => ! $done,
                            ])>{{ $done ? 'Feito' : 'Pendente' }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8 rounded-2xl border border-yellow-300/20 bg-yellow-300/10 p-5">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">O que você desbloqueia</p>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-yellow-50/90">
                        <li>Agenda online com horários reais da loja</li>
                        <li>Atendente virtual no WhatsApp qualificando e agendando</li>
                        <li>Landing page pública para captar leads</li>
                    </ul>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
                    <a href="{{ route('onboarding.show', ['step' => $currentStep]) }}" class="cursor-pointer rounded-2xl border border-white/10 bg-white/[.04] px-6 py-3 text-center text-sm font-bold text-zinc-100 transition hover:bg-white/[.08]">Continuar configuração</a>

                    <form method="POST" action="{{ route('onboarding.dismiss') }}">
                        @csrf
                        <button type="submit" class="w-full cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 sm:w-auto">Ir para o dashboard</button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-zinc-500">Depois, acesse Horários, Serviços, Empresa, Atendente virtual e Landing page pelo menu Configurações.</p>
            </section>
        </div>
    </main>
</body>
</html>
