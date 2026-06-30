@php
    $primaryCta = route('signup.create');
    $storefrontUrl = $tenant ? route('storefront', $tenant) : '#dashboard';

    $problems = [
        ['Mensagens sem resposta', 'O lead quente chama no WhatsApp, espera alguns minutos e procura quem atende primeiro.'],
        ['Orçamentos esquecidos', 'A oportunidade fica parada porque ninguém lembrou de voltar no momento certo.'],
        ['Agenda vazia', 'Dias com baixa ocupação aparecem tarde demais para reagir com inteligência.'],
        ['Follow-up inexistente', 'Clientes satisfeitos somem porque ninguém os chama para o próximo cuidado.'],
        ['Venda no improviso', 'A equipe trabalha muito, mas sem previsibilidade comercial.'],
        ['Faturamento perdido', 'Cada demora abre espaço para concorrentes mais rápidos.'],
    ];

    $solutions = [
        ['Atendimento automático', 'Responde com contexto, qualifica a necessidade e conduz o cliente para a melhor próxima ação.'],
        ['Agendamento inteligente', 'Transforma interesse em horário confirmado sem depender da disponibilidade do proprietário.'],
        ['Recuperação de clientes', 'Identifica quem pode voltar e sugere abordagens antes que a relação esfrie.'],
        ['Follow-up automático', 'Mantém propostas, retornos e pós-venda vivos no tempo certo.'],
        ['Inteligência comercial', 'Encontra gargalos, oportunidades e movimentos para vender melhor.'],
        ['Dashboard em tempo real', 'Mostra o que aconteceu, o que importa agora e onde agir primeiro.'],
        ['IA trabalhando 24 horas', 'Enquanto a loja fecha, a operação comercial continua acordada.'],
    ];

    $flow = [
        ['Cliente chama', 'WhatsApp, landing page ou link de agendamento.'],
        ['GarageON atende', 'A conversa continua mesmo fora do horário comercial.'],
        ['Agenda confirma', 'O interesse vira compromisso com clareza para cliente e equipe.'],
        ['Follow-up acontece', 'Quem não fechou recebe o próximo contato no momento certo.'],
        ['Cliente retorna', 'Relacionamentos deixam de depender da memória da equipe.'],
        ['Empresa vende mais', 'Mais oportunidades chegam prontas para virar receita.'],
    ];

    $benefits = [
        'Menos atendimento manual',
        'Mais vendas sem aumentar a equipe',
        'Mais clientes retornando',
        'Agenda mais cheia e previsível',
        'Empresa organizada em um único cockpit',
        'IA trabalhando nos bastidores',
        'Crescimento com rotina comercial clara',
    ];

    $metrics = [
        ['36', 'novos agendamentos', 'Últimos 7 dias'],
        ['18', 'clientes recuperados', 'Oportunidades que voltaram'],
        ['82%', 'agenda preenchida', 'Capacidade da semana'],
        ['R$ 24,8k', 'faturamento gerado', 'Origem rastreada'],
        ['147', 'follow-ups realizados', 'Sem depender da memória'],
        ['412', 'mensagens respondidas', 'Atendimento sempre ON'],
    ];

    $modules = [
        ['GarageON AI', 'Inteligência de atendimento, recomendação e recuperação.'],
        ['GarageON CRM', 'Clientes, histórico, preferências e oportunidades.'],
        ['GarageON Agenda', 'Horários, equipe, serviços e confirmações.'],
        ['GarageON Marketing', 'Campanhas, retornos e relacionamento ativo.'],
        ['GarageON Finance', 'Receita, recorrência e visão comercial.'],
        ['GarageON Ads', 'Leads e campanhas conectadas ao funil real.'],
        ['GarageON Insights', 'Indicadores que mostram onde crescer.'],
        ['GarageON Voice', 'Experiências de voz para atendimento assistido.'],
        ['GarageON Connect', 'Integrações para manter tudo no mesmo fluxo.'],
        ['GarageON Marketplace', 'Soluções e parceiros para expandir a operação.'],
        ['GarageON Academy', 'Treinamento para transformar sistema em crescimento.'],
    ];

    $testimonials = [
        ['Marcos T.', 'Detailer e proprietário', 'Antes eu respondia cliente de noite e ainda assim perdia orçamento. Hoje acordo com pedidos organizados e follow-ups prontos para minha equipe assumir.'],
        ['Renata C.', 'Centro automotivo premium', 'A GarageON trouxe clareza. Não virou mais um sistema para alimentar; virou uma rotina comercial que mostra onde estamos deixando dinheiro na mesa.'],
        ['Igor M.', 'Especialista em vitrificação', 'O que mais mudou foi a velocidade. O cliente chama, recebe atenção e chega muito mais preparado para agendar.'],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GarageON | Sistema Operacional Inteligente para Empresas Automotivas</title>
    <meta name="description" content="Mantenha sua estética automotiva sempre ON com IA para WhatsApp, agenda, follow-up, recuperação de clientes, CRM e crescimento comercial 24 horas por dia.">
    <meta name="keywords" content="GarageON, sistema para estética automotiva, software para detailing, CRM automotivo, agenda automotiva, IA para WhatsApp, lava-rápido premium, vitrificação, PPF, envelopamento, marketing automotivo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="GarageON | Empresa Sempre ON">
    <meta property="og:description" content="O primeiro Sistema Operacional Inteligente para Empresas Automotivas. IA, WhatsApp, agenda, clientes e crescimento em uma única plataforma.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="GarageON">
    <meta property="og:image" content="{{ asset('img/logo-vertical.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GarageON | Sua empresa sempre ON">
    <meta name="twitter:description" content="Enquanto você cuida da operação, a GarageON trabalha no crescimento.">

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "name": "GarageON",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "description": "Sistema Operacional Inteligente para empresas de estética automotiva com IA, WhatsApp, agenda, CRM, marketing e insights comerciais.",
            "url": "{{ url('/') }}",
            "offers": {
                "@@type": "Offer",
                "category": "SaaS B2B",
                "availability": "https://schema.org/InStock"
            },
            "audience": {
                "@@type": "Audience",
                "audienceType": "Empresas de estética automotiva, detailing, PPF, vitrificação, envelopamento e lava-rápidos premium"
            }
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @@media (prefers-reduced-motion: no-preference) {
            [data-reveal] { animation: rise-in .8s ease both; }
            [data-float] { animation: soft-float 6s ease-in-out infinite; }
        }

        @@keyframes rise-in {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @@keyframes soft-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
    </style>
</head>
<body class="min-h-screen bg-[#0B0B0B] text-white antialiased selection:bg-yellow-300 selection:text-black">
    <main class="relative overflow-hidden">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_20%_0%,rgba(255,196,0,.18),transparent_32%),radial-gradient(circle_at_80%_15%,rgba(255,255,255,.08),transparent_28%),linear-gradient(180deg,#050505_0%,#0B0B0B_42%,#050505_100%)]"></div>
        <div class="pointer-events-none fixed inset-x-0 top-0 z-40 h-px bg-gradient-to-r from-transparent via-yellow-300 to-transparent"></div>

        <header class="sticky top-0 z-30 border-b border-white/10 bg-black/70 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-10" aria-label="Navegação principal">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="GarageON Home">
                    <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-10 w-auto">
                </a>
                <div class="hidden items-center gap-7 text-sm text-zinc-300 lg:flex">
                    <a href="#problema" class="transition hover:text-yellow-300">Problema</a>
                    <a href="#solucao" class="transition hover:text-yellow-300">Solução</a>
                    <a href="#dashboard" class="transition hover:text-yellow-300">Dashboard</a>
                    <a href="#ecossistema" class="transition hover:text-yellow-300">Ecossistema</a>
                </div>
                <a href="{{ $primaryCta }}" class="rounded-2xl bg-yellow-300 px-5 py-3 text-sm font-black text-black shadow-[0_0_36px_rgba(255,196,0,.25)] transition hover:-translate-y-0.5 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:ring-offset-2 focus:ring-offset-black">
                    Quero conhecer
                </a>
            </nav>
        </header>

        <section class="relative mx-auto grid min-h-[calc(100vh-73px)] max-w-7xl items-center gap-12 px-6 py-16 lg:grid-cols-[1.02fr_.98fr] lg:px-10 lg:py-24" aria-labelledby="hero-title">
            <div data-reveal>
                <p class="mb-6 inline-flex rounded-full border border-yellow-300/25 bg-yellow-300/10 px-4 py-2 text-xs font-black uppercase tracking-[.28em] text-yellow-200">
                    Empresa Sempre ON
                </p>
                <h1 id="hero-title" class="max-w-4xl font-orbitron text-5xl font-black leading-[.95] tracking-tight text-white md:text-7xl xl:text-8xl">
                    O crescimento da sua estética automotiva não pode dormir.
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-300 md:text-xl">
                    A GarageON é o primeiro Sistema Operacional Inteligente para Empresas Automotivas. Enquanto você cuida da operação, ela atende, agenda, recupera clientes e encontra oportunidades para vender mais.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $primaryCta }}" class="rounded-2xl bg-yellow-300 px-7 py-4 text-center font-black text-black shadow-[0_0_44px_rgba(255,196,0,.28)] transition hover:-translate-y-1 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:ring-offset-2 focus:ring-offset-black">
                        Quero conhecer a GarageON
                    </a>
                    <a href="#como-funciona" class="rounded-2xl border border-white/15 bg-white/[.03] px-7 py-4 text-center font-bold text-white transition hover:border-yellow-300/60 hover:text-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:ring-offset-2 focus:ring-offset-black">
                        Ver como ela trabalha
                    </a>
                </div>
                <div class="mt-8 flex flex-wrap gap-3 text-sm text-zinc-300">
                    @foreach (['IA', 'WhatsApp', 'Agenda', 'Clientes', 'Crescimento'] as $item)
                        <span class="rounded-full border border-white/10 bg-white/[.04] px-4 py-2">{{ $item }}</span>
                    @endforeach
                </div>
            </div>

            <div class="relative" data-reveal data-float>
                <div class="absolute -inset-8 rounded-full bg-yellow-300/10 blur-3xl"></div>
                <div class="relative rounded-[2rem] border border-white/10 bg-white/[.05] p-4 shadow-2xl backdrop-blur-xl">
                    <div class="rounded-[1.5rem] border border-yellow-300/20 bg-[#0B0B0B] p-5">
                        <div class="flex items-center justify-between border-b border-white/10 pb-5">
                            <div>
                                <p class="text-xs uppercase tracking-[.28em] text-yellow-300">Cockpit comercial</p>
                                <h2 class="mt-2 font-orbitron text-2xl font-black">Sua empresa acordou melhor do que ontem.</h2>
                            </div>
                            <span class="rounded-full border border-yellow-300/30 bg-yellow-300/10 px-3 py-1 text-xs font-bold text-yellow-200">ON 24h</span>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <article class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                <p class="text-sm text-zinc-400">Mensagens respondidas</p>
                                <strong class="mt-3 block font-orbitron text-4xl text-yellow-300">412</strong>
                                <span class="mt-2 block text-xs text-zinc-500">sem fila acumulada</span>
                            </article>
                            <article class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                <p class="text-sm text-zinc-400">Agenda preenchida</p>
                                <strong class="mt-3 block font-orbitron text-4xl text-white">82%</strong>
                                <span class="mt-2 block text-xs text-zinc-500">semana em movimento</span>
                            </article>
                            <article class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                <p class="text-sm text-zinc-400">Clientes recuperados</p>
                                <strong class="mt-3 block font-orbitron text-4xl text-white">18</strong>
                                <span class="mt-2 block text-xs text-zinc-500">retornos acionados</span>
                            </article>
                            <article class="rounded-2xl bg-yellow-300 p-4 text-black">
                                <p class="text-sm font-black">Oportunidade para hoje</p>
                                <strong class="mt-3 block font-orbitron text-2xl">R$ 8,4k em propostas quentes</strong>
                            </article>
                        </div>

                        <div class="mt-5 rounded-2xl border border-white/10 bg-black p-4">
                            <p class="text-sm text-zinc-300">Encontrei clientes esperando sua atenção.</p>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-[74%] rounded-full bg-yellow-300"></div>
                            </div>
                            <p class="mt-3 text-xs text-zinc-500">74% das oportunidades já têm próximo passo sugerido.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="problema" class="relative border-y border-white/10 bg-white/[.025] px-6 py-24 lg:px-10" aria-labelledby="problem-title">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-12 lg:grid-cols-[.9fr_1.1fr] lg:items-end">
                    <div data-reveal>
                        <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">O problema</p>
                        <h2 id="problem-title" class="mt-4 max-w-3xl text-4xl font-black leading-tight md:text-6xl">Toda empresa fecha. Os clientes não.</h2>
                        <p class="mt-6 text-lg leading-8 text-zinc-300">Eles continuam pesquisando, comparando e chamando quem responde primeiro. Quando a operação depende apenas de tempo humano, crescimento vira improviso.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($problems as [$title, $body])
                            <article class="group rounded-3xl border border-white/10 bg-[#111111] p-6 transition hover:-translate-y-1 hover:border-yellow-300/40 hover:bg-[#151515]" data-reveal>
                                <span class="mb-5 inline-flex size-10 items-center justify-center rounded-2xl border border-yellow-300/25 bg-yellow-300/10 text-yellow-200">!</span>
                                <h3 class="font-orbitron text-lg font-black text-white">{{ $title }}</h3>
                                <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $body }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="solucao" class="mx-auto max-w-7xl px-6 py-24 lg:px-10" aria-labelledby="solution-title">
            <div class="mx-auto max-w-3xl text-center" data-reveal>
                <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">A solução</p>
                <h2 id="solution-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">A GarageON trabalha enquanto você trabalha.</h2>
                <p class="mt-6 text-lg leading-8 text-zinc-300">Não é CRM. Não é ERP. Não é chatbot. É uma plataforma que mantém sua empresa funcionando 24 horas por dia através de inteligência comercial.</p>
            </div>

            <div class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($solutions as [$title, $body])
                    <article class="rounded-3xl border border-white/10 bg-gradient-to-b from-white/[.06] to-white/[.025] p-6 transition hover:-translate-y-1 hover:border-yellow-300/40" data-reveal>
                        <span class="inline-flex size-11 items-center justify-center rounded-2xl bg-yellow-300 text-lg font-black text-black">✓</span>
                        <h3 class="mt-6 font-orbitron text-xl font-black text-white">{{ $title }}</h3>
                        <p class="mt-4 text-sm leading-6 text-zinc-400">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="como-funciona" class="border-y border-white/10 bg-black px-6 py-24 lg:px-10" aria-labelledby="flow-title">
            <div class="mx-auto max-w-7xl">
                <div class="mb-12 max-w-3xl" data-reveal>
                    <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Como funciona</p>
                    <h2 id="flow-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">Do primeiro contato ao retorno do cliente.</h2>
                </div>
                <div class="grid gap-4 lg:grid-cols-6">
                    @foreach ($flow as $index => [$title, $body])
                        <article class="relative rounded-3xl border border-white/10 bg-[#111111] p-5" data-reveal>
                            <span class="font-orbitron text-xs font-black text-yellow-300">0{{ $index + 1 }}</span>
                            <h3 class="mt-5 font-orbitron text-lg font-black">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-12 px-6 py-24 lg:grid-cols-[.85fr_1.15fr] lg:px-10" aria-labelledby="benefits-title">
            <div data-reveal>
                <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Benefícios</p>
                <h2 id="benefits-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">Menos correria. Mais crescimento.</h2>
                <p class="mt-6 text-lg leading-8 text-zinc-300">A GarageON tira o crescimento do improviso e transforma rotina comercial em um fluxo vivo, organizado e sempre atento.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($benefits as $benefit)
                    <div class="rounded-3xl border border-white/10 bg-white/[.04] p-6 text-lg font-bold text-white transition hover:border-yellow-300/40" data-reveal>
                        <span class="mb-4 block text-yellow-300">ON</span>
                        {{ $benefit }}
                    </div>
                @endforeach
            </div>
        </section>

        <section id="dashboard" class="relative border-y border-white/10 bg-white/[.025] px-6 py-24 lg:px-10" aria-labelledby="dashboard-title">
            <div class="mx-auto max-w-7xl">
                <div class="mb-12 flex flex-col justify-between gap-6 lg:flex-row lg:items-end" data-reveal>
                    <div>
                        <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Dashboard</p>
                        <h2 id="dashboard-title" class="mt-4 max-w-3xl text-4xl font-black leading-tight md:text-6xl">Números que mostram onde a empresa está crescendo.</h2>
                    </div>
                    <a href="{{ $storefrontUrl }}" class="w-fit rounded-2xl border border-white/15 px-6 py-3 text-sm font-bold text-white transition hover:border-yellow-300/60 hover:text-yellow-200">Ver experiência demonstrativa</a>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-[#0B0B0B] p-4 shadow-2xl lg:p-6" data-reveal>
                    <div class="mb-6 flex flex-col justify-between gap-4 border-b border-white/10 pb-5 md:flex-row md:items-center">
                        <div>
                            <p class="text-xs uppercase tracking-[.28em] text-zinc-500">Leitura executiva</p>
                            <h3 class="mt-2 font-orbitron text-2xl font-black">Encontrei oportunidades esperando sua atenção.</h3>
                        </div>
                        <span class="rounded-full border border-yellow-300/30 bg-yellow-300/10 px-4 py-2 text-sm font-bold text-yellow-200">Atualizado agora</span>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($metrics as [$value, $label, $context])
                            <article class="rounded-3xl border border-white/10 bg-white/[.04] p-6">
                                <p class="text-sm text-zinc-400">{{ $label }}</p>
                                <strong class="mt-4 block font-orbitron text-4xl font-black text-white">{{ $value }}</strong>
                                <span class="mt-3 block text-xs uppercase tracking-[.18em] text-yellow-300">{{ $context }}</span>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="ecossistema" class="mx-auto max-w-7xl px-6 py-24 lg:px-10" aria-labelledby="ecosystem-title">
            <div class="grid gap-12 lg:grid-cols-[.8fr_1.2fr]">
                <div data-reveal>
                    <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Ecossistema</p>
                    <h2 id="ecosystem-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">Tudo faz parte de um único sistema.</h2>
                    <p class="mt-6 text-lg leading-8 text-zinc-300">Da primeira mensagem ao próximo retorno, cada módulo trabalha conectado para manter a empresa sempre ON.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($modules as [$title, $body])
                        <article class="rounded-3xl border border-white/10 bg-[#111111] p-5 transition hover:-translate-y-1 hover:border-yellow-300/40" data-reveal>
                            <h3 class="font-orbitron text-lg font-black text-yellow-200">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-white/10 bg-black px-6 py-24 lg:px-10" aria-labelledby="why-title">
            <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2" data-reveal>
                    <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Por que escolher</p>
                    <h2 id="why-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">Não somos apenas software. Somos parceiros de crescimento.</h2>
                </div>
                <div class="rounded-[2rem] border border-yellow-300/25 bg-yellow-300 p-8 text-black" data-reveal>
                    <p class="font-orbitron text-3xl font-black leading-tight">Tecnologia invisível. Resultados visíveis.</p>
                    <p class="mt-5 text-sm font-bold leading-6">A IA não aparece como robô. Ela aparece como agenda cheia, cliente atendido, oportunidade recuperada e decisão mais clara.</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-24 lg:px-10" aria-labelledby="testimonials-title">
            <div class="mb-12 max-w-3xl" data-reveal>
                <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Depoimentos</p>
                <h2 id="testimonials-title" class="mt-4 text-4xl font-black leading-tight md:text-6xl">O tipo de resultado que muda a rotina.</h2>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach ($testimonials as [$name, $role, $quote])
                    <figure class="rounded-3xl border border-white/10 bg-white/[.04] p-7" data-reveal>
                        <blockquote class="text-base leading-7 text-zinc-300">"{{ $quote }}"</blockquote>
                        <figcaption class="mt-8 border-t border-white/10 pt-5">
                            <strong class="block font-orbitron text-lg text-white">{{ $name }}</strong>
                            <span class="mt-1 block text-sm text-yellow-200">{{ $role }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>

        <section id="cta-final" class="px-6 pb-10 lg:px-10" aria-labelledby="final-title">
            <div class="mx-auto max-w-7xl overflow-hidden rounded-[2rem] border border-yellow-300/25 bg-[radial-gradient(circle_at_80%_0%,rgba(255,196,0,.28),transparent_34%),linear-gradient(135deg,#171717,#050505)] p-8 md:p-14" data-reveal>
                <div class="max-w-4xl">
                    <p class="font-orbitron text-sm font-bold uppercase tracking-[.32em] text-yellow-300">Mantenha tudo ON</p>
                    <h2 id="final-title" class="mt-4 text-4xl font-black leading-tight md:text-7xl">Sua empresa pode fechar. Mas seus clientes continuam procurando.</h2>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-300">Mantenha sua empresa sempre ON com uma plataforma que atende, agenda, recupera e mostra onde crescer.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $primaryCta }}" class="rounded-2xl bg-yellow-300 px-8 py-4 text-center font-black text-black shadow-[0_0_44px_rgba(255,196,0,.28)] transition hover:-translate-y-1 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:ring-offset-2 focus:ring-offset-black">
                            Quero conhecer a GarageON
                        </a>
                        <a href="{{ route('admin') }}" class="rounded-2xl border border-white/15 bg-white/[.03] px-8 py-4 text-center font-bold text-white transition hover:border-yellow-300/60 hover:text-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:ring-offset-2 focus:ring-offset-black">
                            Acessar cockpit
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
