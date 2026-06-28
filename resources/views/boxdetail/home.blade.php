<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BoxDetail - SaaS para estética automotiva</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#050505] text-white antialiased">
    <main class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_10%,rgba(250,204,21,.22),transparent_34%),linear-gradient(135deg,rgba(255,255,255,.08)_0,transparent_34%)]"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-yellow-400"></div>

        <section class="relative mx-auto grid min-h-screen max-w-7xl gap-12 px-6 py-8 lg:grid-cols-[1.05fr_.95fr] lg:px-10">
            <nav class="col-span-full flex items-center justify-between">
                <a href="{{ route('home') }}" class="font-orbitron text-2xl font-black tracking-[.22em] text-yellow-300">BOXDETAIL</a>
                <div class="hidden items-center gap-6 text-sm text-zinc-300 md:flex">
                    <a href="#modulos" class="hover:text-yellow-300">Módulos</a>
                    <a href="#planos" class="hover:text-yellow-300">Planos</a>
                    <a href="{{ route('admin') }}" class="rounded-full border border-yellow-300/40 px-4 py-2 text-yellow-200 hover:bg-yellow-300 hover:text-black">Administrador</a>
                </div>
            </nav>

            <div class="flex flex-col justify-center pb-12">
                <p class="mb-5 inline-flex w-fit rounded-full border border-yellow-300/30 bg-yellow-300/10 px-4 py-2 text-xs font-bold uppercase tracking-[.3em] text-yellow-200">
                    Multi-tenant para oficinas premium
                </p>
                <h1 class="font-orbitron text-5xl font-black leading-[.95] tracking-tight text-white md:text-7xl">
                    O motor comercial da estética automotiva.
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-300">
                    Auto-agendamento, WhatsApp 24/7, vendedor digital, retenção ativa, clube de assinatura, fidelidade e landing pages em uma plataforma SaaS para cada cliente operar sua própria loja.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    @if ($tenant)
                        <a href="{{ route('booking', $tenant) }}" class="rounded-full bg-yellow-300 px-7 py-4 text-center font-bold text-black shadow-[0_0_40px_rgba(250,204,21,.35)] transition hover:-translate-y-1">
                            Ver auto-agendamento
                        </a>
                        <a href="{{ route('storefront', $tenant) }}" class="rounded-full border border-white/20 px-7 py-4 text-center font-bold text-white transition hover:border-yellow-300 hover:text-yellow-200">
                            Ver landing page
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center">
                <div class="relative w-full rounded-[2rem] border border-white/10 bg-white/[.06] p-5 shadow-2xl backdrop-blur">
                    <div class="rounded-[1.5rem] border border-yellow-300/20 bg-black p-5">
                        <div class="mb-6 flex items-center justify-between border-b border-white/10 pb-4">
                            <div>
                                <p class="text-xs uppercase tracking-[.25em] text-yellow-300">Cockpit</p>
                                <h2 class="font-orbitron text-2xl font-bold">{{ $tenant?->name ?? 'Loja demonstrativa' }}</h2>
                            </div>
                            <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-sm text-emerald-300">online</span>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-zinc-900 p-4">
                                <p class="text-sm text-zinc-400">Agendamentos via bot</p>
                                <strong class="mt-2 block font-orbitron text-4xl text-yellow-300">24/7</strong>
                            </div>
                            <div class="rounded-2xl bg-zinc-900 p-4">
                                <p class="text-sm text-zinc-400">Dinheiro parado</p>
                                <strong class="mt-2 block font-orbitron text-4xl text-white">R$ 2,5k</strong>
                            </div>
                            <div class="rounded-2xl bg-zinc-900 p-4">
                                <p class="text-sm text-zinc-400">Assinatura ativa</p>
                                <strong class="mt-2 block font-orbitron text-4xl text-white">R$ 349</strong>
                            </div>
                            <div class="rounded-2xl bg-yellow-300 p-4 text-black">
                                <p class="text-sm font-bold">Order bump sugerido</p>
                                <strong class="mt-2 block font-orbitron text-2xl">+ Para-brisas</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="modulos" class="relative mx-auto max-w-7xl px-6 py-24 lg:px-10">
            <div class="mb-10 max-w-3xl">
                <p class="font-orbitron text-sm uppercase tracking-[.35em] text-yellow-300">Escopo BoxDetail</p>
                <h2 class="mt-3 text-4xl font-black">Módulos criados para aumentar agenda, ticket e recorrência.</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Auto-agendamento + Chatbot', 'Link externo e WhatsApp lendo horários disponíveis para marcar sem atendimento humano.'],
                    ['Vendedor Digital', 'Varredura de orçamentos sem resposta e clientes inativos com sugestões de abordagem.'],
                    ['Pós-venda ativo', 'Lembretes automáticos baseados no ciclo de vida de vitrificação, coating e lavagens.'],
                    ['Clube de Assinatura', 'Gestão de recorrência mensal no cartão para estabilizar o caixa da oficina.'],
                    ['Fidelidade + Order Bump', 'Pontos, selos digitais e ofertas inteligentes durante orçamento ou agendamento.'],
                    ['Landing Pages', 'Páginas por loja para captar leads, divulgar serviços e enviar para agendamento.'],
                ] as [$title, $body])
                    <article class="rounded-3xl border border-white/10 bg-white/[.04] p-6">
                        <h3 class="font-orbitron text-xl font-bold text-yellow-200">{{ $title }}</h3>
                        <p class="mt-4 text-sm leading-6 text-zinc-300">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="planos" class="relative border-t border-white/10 bg-white/[.03] px-6 py-24 lg:px-10">
            <div class="mx-auto grid max-w-7xl gap-5 md:grid-cols-3">
                @foreach ($plans as $plan)
                    <article class="rounded-3xl border border-white/10 bg-black p-7">
                        <p class="font-orbitron text-xl font-bold text-yellow-300">{{ $plan->name }}</p>
                        <p class="mt-4 text-4xl font-black">R$ {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-zinc-400">até {{ $plan->locations_limit }} loja(s)</p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-300">
                            @foreach ($plan->features ?? [] as $feature)
                                <li>• {{ $feature }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
