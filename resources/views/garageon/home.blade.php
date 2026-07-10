@php
    $primaryCta = route('signup.create');
    $loginUrl = route('login');
    $storefrontUrl = $tenant ? route('storefront', $tenant) : '#planos';

    $plans = ($plans ?? collect())->sortBy('monthly_price')->values();
    $trialDays = 14;
    $priceFmt = fn ($value) => 'R$ ' . number_format((float) $value, 0, ',', '.');
    $planCta = fn ($slug) => route('signup.create', ['plano' => $slug]);
    $highlightSlug = 'performance';

    $problems = [
        ['Mensagens sem resposta', 'O lead quente chama no WhatsApp, espera alguns minutos e procura quem atende primeiro.'],
        ['Orçamentos esquecidos', 'A oportunidade fica parada porque ninguém lembrou de voltar no momento certo.'],
        ['Agenda vazia', 'Dias com baixa ocupação aparecem tarde demais para reagir com inteligência.'],
        ['Follow-up inexistente', 'Clientes satisfeitos somem porque ninguém os chama para o próximo cuidado.'],
        ['Venda no improviso', 'A equipe trabalha muito, mas sem previsibilidade comercial.'],
        ['Faturamento perdido', 'Cada demora abre espaço para concorrentes mais rápidos.'],
    ];

    $solutions = [
        ['Atendente virtual 24/7', 'IA responde no WhatsApp com contexto, qualifica a necessidade e conduz o cliente para o agendamento.'],
        ['Agenda que preenche sozinha', 'Transforma interesse em horário confirmado sem depender da disponibilidade do proprietário.'],
        ['Recuperação de clientes', 'Identifica quem pode voltar e dispara a abordagem certa antes que a relação esfrie.'],
        ['Follow-up automático', 'Mantém propostas, retornos e pós-venda vivos no tempo certo, sem esforço manual.'],
        ['Orçamentos e vendas', 'Envie propostas com link público, feche vendas e acompanhe o faturamento por origem.'],
        ['Landing page própria', 'Cada loja ganha uma página com domínio próprio para captar e converter leads.'],
        ['Clube de assinatura', 'Crie recorrência com planos mensais e receita previsível todo mês.'],
        ['Fidelidade & pós-venda', 'Programa de pontos e automações de retorno para transformar clientes em recorrentes.'],
        ['Cockpit em tempo real', 'Um painel mostra o que aconteceu, o que importa agora e onde agir primeiro.'],
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
        'Receita recorrente com clube de assinatura',
    ];

    $modules = [
        ['Atendente Virtual', 'IA de atendimento no WhatsApp que qualifica, responde e agenda.'],
        ['CRM Automotivo', 'Clientes, veículos, histórico, preferências e oportunidades.'],
        ['Agenda Inteligente', 'Horários, equipe, serviços, confirmações e disponibilidade.'],
        ['Orçamentos & Vendas', 'Propostas com link público, fechamento e faturamento rastreado.'],
        ['Clube de Assinatura', 'Recorrência mensal para gerar receita previsível.'],
        ['Landing Pages', 'Página de captação com domínio próprio para cada loja.'],
        ['Vendedor Digital', 'Alertas de orçamentos parados e clientes inativos para agir na hora.'],
        ['Pós-venda Automático', 'Automações de retorno no ciclo certo de cada serviço.'],
        ['Fidelidade', 'Programa de pontos para transformar clientes em recorrentes.'],
    ];

    $testimonials = [
        ['Marcos T.', 'Detailer e proprietário', 'Antes eu respondia cliente de noite e ainda assim perdia orçamento. Hoje acordo com pedidos organizados e follow-ups prontos para minha equipe assumir.'],
        ['Renata C.', 'Centro automotivo premium', 'A GarageON trouxe clareza. Não virou mais um sistema para alimentar; virou uma rotina comercial que mostra onde estamos deixando dinheiro na mesa.'],
        ['Igor M.', 'Especialista em vitrificação', 'O que mais mudou foi a velocidade. O cliente chama, recebe atenção e chega muito mais preparado para agendar.'],
    ];

    $faqs = [
        ['Preciso falar com um vendedor para começar?', 'Não. Você cria sua conta agora mesmo, cai direto no painel e já começa a usar. Nenhum contato humano é necessário para ativar a GarageON.'],
        ['Como funciona o período de teste?', "Você tem {$trialDays} dias para testar a plataforma. Cadastre-se, configure seus serviços e ative o atendimento sem compromisso."],
        ['Preciso instalar alguma coisa?', 'Não. A GarageON é 100% na nuvem. Acesse pelo navegador, no computador ou no celular, sem instalar nada.'],
        ['A IA responde no meu WhatsApp?', 'Sim. O atendente virtual conversa com seus clientes no WhatsApp, qualifica a necessidade e conduz para o agendamento, mesmo fora do horário comercial.'],
        ['Consigo usar meu próprio domínio?', 'Sim. Cada loja tem uma landing page própria e você pode apontar seu domínio via CNAME para deixar tudo com a sua marca.'],
        ['Posso trocar de plano depois?', 'Sim. Você começa em qualquer plano e faz upgrade ou downgrade quando quiser, conforme o volume da sua operação cresce.'],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GarageON | Software com IA para Estética Automotiva — Agenda, WhatsApp e CRM</title>
    <meta name="description" content="Crie sua conta em minutos e coloque sua estética automotiva no automático: IA no WhatsApp, agenda online 24/7, CRM, orçamentos, clube de assinatura e landing page própria. Teste grátis por {{ $trialDays }} dias, sem cartão.">
    <meta name="keywords" content="GarageON, software para estética automotiva, sistema para detailing, CRM automotivo, agenda online automotiva, IA para WhatsApp, atendente virtual, lava-rápido premium, vitrificação, PPF, envelopamento, clube de assinatura automotivo, landing page oficina">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#050505">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="GarageON | Sua estética automotiva sempre ON">
    <meta property="og:description" content="IA no WhatsApp, agenda online 24/7, CRM, orçamentos e clube de assinatura. Crie sua conta e comece a usar hoje. Teste grátis por {{ $trialDays }} dias.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="GarageON">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:image" content="{{ asset('img/logo-vertical.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GarageON | Sua estética automotiva sempre ON">
    <meta name="twitter:description" content="Crie sua conta e coloque o atendimento, a agenda e as vendas no automático. Teste grátis por {{ $trialDays }} dias.">
    <meta name="twitter:image" content="{{ asset('img/logo-vertical.png') }}">

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "name": "GarageON",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "description": "Software com IA para estética automotiva: atendente virtual no WhatsApp, agenda online 24/7, CRM, orçamentos, clube de assinatura e landing page própria.",
            "url": "{{ url('/') }}",
            "aggregateRating": {
                "@@type": "AggregateRating",
                "ratingValue": "4.9",
                "reviewCount": "128"
            },
            "offers": [
                @foreach ($plans as $plan)
                {
                    "@@type": "Offer",
                    "name": "{{ $plan->name }}",
                    "price": "{{ number_format((float) $plan->monthly_price, 2, '.', '') }}",
                    "priceCurrency": "BRL",
                    "category": "SaaS B2B",
                    "availability": "https://schema.org/InStock",
                    "url": "{{ $planCta($plan->slug) }}"
                }@if (! $loop->last),@endif
                @endforeach
            ]
        }
    </script>

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "FAQPage",
            "mainEntity": [
                @foreach ($faqs as [$question, $answer])
                {
                    "@@type": "Question",
                    "name": "{{ $question }}",
                    "acceptedAnswer": { "@@type": "Answer", "text": "{{ $answer }}" }
                }@if (! $loop->last),@endif
                @endforeach
            ]
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @@media (prefers-reduced-motion: no-preference) {
            [data-reveal] { animation: rise-in 1.1s cubic-bezier(.22,.61,.36,1) both; }
        }

        @@keyframes rise-in {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @@keyframes drift {
            0%, 100% { transform: translate3d(0,0,0) scale(1); }
            50% { transform: translate3d(0,-14px,0) scale(1.04); }
        }

        @@keyframes scan-line {
            0% { transform: translateY(-110%); opacity: 0; }
            18%, 82% { opacity: .65; }
            100% { transform: translateY(610%); opacity: 0; }
        }
        @@media (prefers-reduced-motion: no-preference) {
            .drift { animation: drift 9s ease-in-out infinite; }
            .scan-line { animation: scan-line 5.5s ease-in-out infinite; }
        }
    </style>
</head>
<body class="marketing min-h-screen bg-black text-white antialiased selection:bg-yellow-300/90 selection:text-black">
    <main class="relative">
        <header class="sticky top-0 z-40 border-b border-white/[.06] bg-black/70 backdrop-blur-2xl">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-3.5 lg:px-8" aria-label="Navegação principal">
                <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="GarageON Home">
                    <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-8 w-auto">
                </a>
                <div class="hidden items-center gap-8 text-[13px] font-medium text-zinc-400 lg:flex">
                    <a href="#solucao" class="transition hover:text-white">Recursos</a>
                    <a href="#como-funciona" class="transition hover:text-white">Como funciona</a>
                    <a href="#planos" class="transition hover:text-white">Planos</a>
                    <a href="#faq" class="transition hover:text-white">Dúvidas</a>
                </div>
                <div class="flex items-center gap-5">
                    <a href="{{ $loginUrl }}" class="hidden text-[13px] font-medium text-zinc-300 transition hover:text-white sm:block">Entrar</a>
                    <a href="{{ $primaryCta }}" class="rounded-full bg-white px-5 py-2 text-[13px] font-semibold text-black transition hover:bg-zinc-200">
                        Criar conta
                    </a>
                </div>
            </nav>
        </header>

        {{-- HERO --}}
        <section class="relative isolate flex min-h-[92vh] flex-col items-center overflow-hidden px-6 pb-16 pt-24 text-center md:pt-28" aria-labelledby="hero-title">
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="absolute inset-0 bg-cover opacity-90 md:hidden" style="background-image: url('{{ asset('img/bg-mobile.png') }}'); background-position: center bottom;"></div>
                <div class="absolute inset-0 hidden bg-cover opacity-90 md:block" style="background-image: url('{{ asset('img/bg.png') }}'); background-position: center bottom; background-size: cover;"></div>
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,.34),rgba(0,0,0,.50)_44%,rgba(0,0,0,.88)_76%,#000_100%),radial-gradient(circle_at_50%_30%,rgba(0,0,0,.22),rgba(0,0,0,.52)_64%)]"></div>
                <div class="drift absolute left-1/2 top-[-18%] size-[86vw] max-w-[980px] -translate-x-1/2 rounded-full bg-[radial-gradient(circle,rgba(250,204,21,.16),transparent_62%)] blur-2xl"></div>
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.035)_1px,transparent_1px)] bg-[size:72px_72px] [mask-image:linear-gradient(to_bottom,black,transparent_78%)]"></div>
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-yellow-300/50 to-transparent"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_90%_55%_at_50%_112%,rgba(255,255,255,.08),transparent_62%)]"></div>
            </div>
            <div data-reveal class="relative z-10 mx-auto max-w-5xl">
                <p class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[.04] px-4 py-1.5 text-[12px] font-medium tracking-wide text-zinc-300 shadow-[0_0_40px_rgba(250,204,21,.08)]">
                    <span class="size-1.5 rounded-full bg-yellow-300"></span>
                    IA no WhatsApp · agenda 24/7 · teste grátis por {{ $trialDays }} dias
                </p>
                <h1 id="hero-title" class="title-orbitron text-balance text-5xl leading-[1.02] text-white md:text-7xl xl:text-[5.8rem]">
                    O cockpit que mantém sua estética automotiva sempre ON.
                </h1>
                <p class="mx-auto mt-7 max-w-3xl text-balance text-xl font-normal leading-8 text-zinc-400">
                    A GarageON atende leads, agenda serviços, recupera clientes e mostra onde agir primeiro. Menos improviso no WhatsApp. Mais carros entrando no box.
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-x-8 gap-y-4 sm:flex-row">
                    <a href="{{ $primaryCta }}" class="w-full rounded-full bg-yellow-300 px-8 py-3.5 text-center text-[15px] font-semibold text-black shadow-[0_0_38px_rgba(250,204,21,.22)] transition hover:bg-yellow-200 sm:w-auto">
                        Criar minha conta grátis
                    </a>
                    <a href="#planos" class="group inline-flex items-center gap-1.5 text-[15px] font-medium text-white transition hover:text-yellow-200">
                        Ver planos e preços
                        <span class="transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            {{-- Mockup do cockpit (bloco bento grande) --}}
            <div class="relative z-10 mt-16 w-full max-w-6xl" data-reveal>
                <div class="relative rounded-[34px] border border-white/[.08] bg-gradient-to-b from-[#171717] to-[#070707] p-2 shadow-[0_44px_120px_-64px_rgba(0,0,0,.95)] md:p-3">
                    <div class="pointer-events-none absolute -inset-px rounded-[34px] bg-[linear-gradient(120deg,transparent,rgba(250,204,21,.25),transparent)] opacity-60"></div>
                    <div class="relative overflow-hidden rounded-[26px] border border-white/[.06] bg-[#080808] text-left">
                        <div class="scan-line pointer-events-none absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-yellow-300/0 via-yellow-300/10 to-yellow-300/0"></div>
                        <div class="grid gap-0 lg:grid-cols-[1fr_.72fr]">
                            <div class="p-5 md:p-8">
                                <div class="flex items-center justify-between border-b border-white/[.06] pb-5">
                                    <div>
                                        <p class="text-[12px] font-medium text-zinc-500">Cockpit comercial</p>
                                        <h2 class="mt-1.5 text-lg font-semibold tracking-[-0.01em] text-white">Hoje, sua operação já acordou vendendo.</h2>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-yellow-300/25 bg-yellow-300/[.06] px-3 py-1 text-[11px] font-medium text-yellow-100"><span class="size-1.5 rounded-full bg-yellow-300"></span>ON 24h</span>
                                </div>
                                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                    <article class="rounded-2xl border border-white/[.06] bg-white/[.025] p-5">
                                        <p class="text-[13px] text-zinc-500">Leads atendidos</p>
                                        <strong class="mt-2 block text-3xl font-semibold tracking-[-0.02em] text-white">412</strong>
                                    </article>
                                    <article class="rounded-2xl border border-white/[.06] bg-white/[.025] p-5">
                                        <p class="text-[13px] text-zinc-500">Agenda ocupada</p>
                                        <strong class="mt-2 block text-3xl font-semibold tracking-[-0.02em] text-white">82%</strong>
                                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/[.08]"><div class="h-full w-[82%] rounded-full bg-yellow-300"></div></div>
                                    </article>
                                    <article class="rounded-2xl border border-white/[.06] bg-white/[.025] p-5">
                                        <p class="text-[13px] text-zinc-500">Clientes recuperados</p>
                                        <strong class="mt-2 block text-3xl font-semibold tracking-[-0.02em] text-white">18</strong>
                                    </article>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-4 rounded-2xl border border-yellow-300/20 bg-yellow-300/[.06] p-5">
                                    <div>
                                        <p class="text-[13px] font-medium text-yellow-200/90">Oportunidade para hoje</p>
                                        <strong class="mt-1 block text-xl font-semibold tracking-[-0.01em] text-white">R$ 8,4k em propostas quentes</strong>
                                    </div>
                                    <span class="hidden shrink-0 rounded-full bg-yellow-300 px-4 py-2 text-[13px] font-semibold text-black sm:block">Agir agora</span>
                                </div>
                            </div>
                            <div class="border-t border-white/[.06] bg-[radial-gradient(circle_at_50%_0%,rgba(250,204,21,.14),transparent_42%)] p-5 md:p-8 lg:border-l lg:border-t-0">
                                <p class="text-[12px] font-medium text-zinc-500">Fluxo ao vivo</p>
                                <div class="mt-5 space-y-3">
                                    <div class="rounded-2xl border border-white/[.06] bg-black/45 p-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-sm font-semibold text-white">Civic Touring</span>
                                            <span class="rounded-full bg-yellow-300 px-2.5 py-1 text-[11px] font-semibold text-black">Vitrificação</span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-zinc-400">Cliente qualificado pela IA. Horário sugerido: quinta, 8h.</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/[.06] bg-black/45 p-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-sm font-semibold text-white">BMW 320i</span>
                                            <span class="rounded-full border border-white/10 px-2.5 py-1 text-[11px] font-medium text-zinc-300">Proposta aberta</span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-zinc-400">Follow-up pronto para recuperar R$ 1.890.</p>
                                    </div>
                                    <div class="rounded-2xl border border-yellow-300/20 bg-yellow-300/[.06] p-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="text-sm font-semibold text-white">Box 03</span>
                                            <span class="text-[11px] font-semibold text-yellow-200">Confirmado</span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-zinc-300">Polimento técnico entrou na agenda sem atendimento manual.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PROBLEMA --}}
        <section id="problema" class="px-6 py-32 lg:px-8" aria-labelledby="problem-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">O problema</p>
                    <h2 id="problem-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Toda empresa fecha.<br class="hidden sm:block"> Os clientes não.</h2>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-400">Eles continuam pesquisando, comparando e chamando quem responde primeiro. Quando a operação depende só de tempo humano, crescimento vira improviso.</p>
                </div>
                <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($problems as [$title, $body])
                        <article class="rounded-[28px] border border-white/[.07] bg-[#0c0c0c] p-8 transition hover:border-white/15" data-reveal>
                            <h3 class="text-lg font-semibold tracking-[-0.01em] text-white">{{ $title }}</h3>
                            <p class="mt-3 text-[15px] leading-7 text-zinc-500">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- SOLUÇÃO — bento assimétrico com mockup de conversa --}}
        <section id="solucao" class="px-6 py-32 lg:px-8" aria-labelledby="solution-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Recursos</p>
                    <h2 id="solution-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Tudo que sua loja precisa para vender mais.</h2>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-400">Não é só CRM. Não é só chatbot. É a plataforma que mantém sua empresa funcionando 24 horas por dia — do primeiro "oi" no WhatsApp até o cliente voltar.</p>
                </div>

                {{-- Bloco bento destaque: atendente virtual com mockup de chat --}}
                <div class="mt-16 grid gap-5 lg:grid-cols-[1.15fr_.85fr]" data-reveal>
                    <div class="relative overflow-hidden rounded-[32px] border border-white/[.08] bg-gradient-to-br from-[#101010] to-[#0a0a0a] p-8 md:p-12">
                        <div class="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-yellow-300/10 blur-3xl"></div>
                        <p class="text-[13px] font-medium text-yellow-300/90">Atendente virtual 24/7</p>
                        <h3 class="mt-3 max-w-md text-2xl font-semibold leading-snug tracking-[-0.01em] text-white md:text-3xl">A IA conversa, qualifica e agenda — mesmo com a loja fechada.</h3>
                        <div class="mt-8 max-w-md space-y-3">
                            <div class="ml-auto w-fit max-w-[85%] rounded-2xl rounded-tr-md bg-yellow-300 px-4 py-2.5 text-[14px] font-medium text-black">Oi! Quanto fica pra vitrificar meu Civic?</div>
                            <div class="w-fit max-w-[85%] rounded-2xl rounded-tl-md bg-white/[.06] px-4 py-2.5 text-[14px] text-zinc-200">Boa noite! A Vitrificação 9H sai R$ 1.890 e leva 1 dia. Tenho quinta às 8h livre — quer que eu reserve?</div>
                            <div class="ml-auto w-fit max-w-[85%] rounded-2xl rounded-tr-md bg-yellow-300 px-4 py-2.5 text-[14px] font-medium text-black">Pode reservar 👍</div>
                            <div class="w-fit max-w-[85%] rounded-2xl rounded-tl-md bg-white/[.06] px-4 py-2.5 text-[14px] text-zinc-200">Agendado ✅ Te vejo na quinta, 8h!</div>
                        </div>
                    </div>
                    <div class="grid gap-5">
                        <div class="rounded-[32px] border border-white/[.08] bg-[#0c0c0c] p-8">
                            <p class="text-[13px] font-medium text-yellow-300/90">Agenda que preenche sozinha</p>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.01em] text-white">Interesse vira horário confirmado.</h3>
                            <div class="mt-6 flex items-end gap-2">
                                @foreach ([40,65,52,80,72,90,60] as $h)
                                    <div class="flex-1 rounded-t bg-gradient-to-t from-yellow-300/30 to-yellow-300" style="height: {{ $h }}px"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-[32px] border border-white/[.08] bg-[#0c0c0c] p-8">
                            <p class="text-[13px] font-medium text-yellow-300/90">Recuperação de clientes</p>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.01em] text-white">Traz de volta quem parou de aparecer.</h3>
                        </div>
                    </div>
                </div>

                {{-- Grid dos demais recursos --}}
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                    @foreach (array_slice($solutions, 3) as [$title, $body])
                        <article class="rounded-[28px] border border-white/[.07] bg-[#0c0c0c] p-8 transition hover:border-white/15">
                            <span class="inline-flex size-9 items-center justify-center rounded-full border border-yellow-300/25 text-yellow-300" aria-hidden="true">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <h3 class="mt-6 text-lg font-semibold tracking-[-0.01em] text-white">{{ $title }}</h3>
                            <p class="mt-2.5 text-[15px] leading-7 text-zinc-500">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- COMO FUNCIONA --}}
        <section id="como-funciona" class="px-6 py-32 lg:px-8" aria-labelledby="flow-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Como funciona</p>
                    <h2 id="flow-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Do primeiro contato ao retorno do cliente.</h2>
                </div>
                <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                    @foreach ($flow as $index => [$title, $body])
                        <article class="rounded-[28px] border border-white/[.07] bg-[#0c0c0c] p-8">
                            <span class="text-sm font-medium tabular-nums text-yellow-300/80">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="mt-4 text-lg font-semibold tracking-[-0.01em]">{{ $title }}</h3>
                            <p class="mt-2.5 text-[15px] leading-7 text-zinc-500">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="mt-14 text-center" data-reveal>
                    <a href="{{ $primaryCta }}" class="inline-flex rounded-full bg-yellow-300 px-8 py-3.5 text-[15px] font-semibold text-black transition hover:bg-yellow-200">
                        Começar agora — grátis por {{ $trialDays }} dias
                    </a>
                </div>
            </div>
        </section>

        {{-- ECOSSISTEMA --}}
        <section class="px-6 py-32 lg:px-8" aria-labelledby="ecosystem-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Ecossistema</p>
                    <h2 id="ecosystem-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Tudo faz parte de um único sistema.</h2>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-400">Da primeira mensagem ao próximo retorno, cada módulo trabalha conectado para manter a empresa sempre ON.</p>
                </div>
                <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                    @foreach ($modules as [$title, $body])
                        <article class="rounded-[28px] border border-white/[.07] bg-[#0c0c0c] p-8 transition hover:border-white/15">
                            <h3 class="text-lg font-semibold tracking-[-0.01em] text-white">{{ $title }}</h3>
                            <p class="mt-2.5 text-[15px] leading-7 text-zinc-500">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- BENEFÍCIOS — bloco bento largo --}}
        <section class="px-6 py-32 lg:px-8" aria-labelledby="benefits-title">
            <div class="mx-auto max-w-6xl">
                <div class="overflow-hidden rounded-[36px] border border-white/[.08] bg-gradient-to-b from-[#111111] to-[#0a0a0a] p-8 md:p-14" data-reveal>
                    <div class="grid gap-12 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                        <div>
                            <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Benefícios</p>
                            <h2 id="benefits-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-5xl">Menos correria. Mais crescimento.</h2>
                            <p class="mt-6 text-lg leading-8 text-zinc-400">A GarageON tira o crescimento do improviso e transforma rotina comercial em um fluxo vivo, organizado e sempre atento.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($benefits as $benefit)
                                <div class="flex items-center gap-3 rounded-2xl border border-white/[.07] bg-black/40 px-5 py-4 text-[15px] font-medium text-zinc-200">
                                    <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-yellow-300/15 text-yellow-300" aria-hidden="true">
                                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </span>
                                    {{ $benefit }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PLANOS --}}
        <section id="planos" class="px-6 py-32 lg:px-8" aria-labelledby="pricing-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Planos</p>
                    <h2 id="pricing-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Escolha, cadastre-se e comece hoje.</h2>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-400">Preços claros, sem taxa de setup e sem fidelidade. Todos começam com {{ $trialDays }} dias grátis. Escolha um plano e crie sua conta em minutos.</p>
                </div>

                @if ($plans->isNotEmpty())
                    <div class="mt-16 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($plans as $plan)
                            @php $isHighlight = $plan->slug === $highlightSlug; @endphp
                            <article
                                class="relative flex flex-col rounded-[32px] border p-8 transition {{ $isHighlight ? 'border-yellow-300/40 bg-gradient-to-b from-[#14140b] to-[#0b0b0a]' : 'border-white/[.08] bg-[#0c0c0c] hover:border-white/15' }}"
                                data-reveal
                            >
                                @if ($isHighlight)
                                    <span class="absolute right-6 top-8 rounded-full bg-yellow-300 px-2.5 py-1 text-[11px] font-semibold text-black">Popular</span>
                                @endif
                                <h3 class="text-base font-semibold tracking-[-0.01em] text-white">{{ $plan->name }}</h3>
                                <div class="mt-4 flex items-baseline gap-1">
                                    <span class="text-4xl font-semibold tracking-[-0.03em] text-white">{{ $priceFmt($plan->monthly_price) }}</span>
                                    <span class="text-sm text-zinc-500">/mês</span>
                                </div>
                                <ul class="mt-7 flex-1 space-y-3 text-sm text-zinc-400">
                                    @foreach (($plan->features ?? []) as $feature)
                                        <li class="flex gap-2.5">
                                            <span class="mt-0.5 shrink-0 text-yellow-300" aria-hidden="true">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            </span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <a
                                    href="{{ $planCta($plan->slug) }}"
                                    class="mt-8 rounded-full px-6 py-3 text-center text-[14px] font-semibold transition {{ $isHighlight ? 'bg-yellow-300 text-black hover:bg-yellow-200' : 'bg-white/[.06] text-white hover:bg-white/10' }}"
                                >
                                    Começar
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <p class="mt-10 text-center text-sm text-zinc-600">Valores em reais (BRL). Troque de plano a qualquer momento.</p>
                @else
                    <div class="mt-16 rounded-[32px] border border-white/[.08] bg-[#0c0c0c] p-12 text-center" data-reveal>
                        <p class="text-lg text-zinc-400">Fale conosco para conhecer os planos disponíveis.</p>
                        <a href="{{ $primaryCta }}" class="mt-6 inline-flex rounded-full bg-yellow-300 px-8 py-3.5 font-semibold text-black transition hover:bg-yellow-200">Criar minha conta grátis</a>
                    </div>
                @endif
            </div>
        </section>

        {{-- DEPOIMENTOS --}}
        <section class="px-6 py-32 lg:px-8" aria-labelledby="testimonials-title">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Depoimentos</p>
                    <h2 id="testimonials-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">O tipo de resultado que muda a rotina.</h2>
                </div>
                <div class="mt-16 grid gap-5 lg:grid-cols-3">
                    @foreach ($testimonials as [$name, $role, $quote])
                        <figure class="flex flex-col rounded-[28px] border border-white/[.07] bg-[#0c0c0c] p-8" data-reveal>
                            <div class="text-yellow-300" aria-hidden="true">★★★★★</div>
                            <blockquote class="mt-5 flex-1 text-[16px] leading-7 text-zinc-300">"{{ $quote }}"</blockquote>
                            <figcaption class="mt-7 border-t border-white/[.06] pt-5">
                                <strong class="block text-[15px] font-semibold text-white">{{ $name }}</strong>
                                <span class="mt-0.5 block text-sm text-zinc-500">{{ $role }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="px-6 py-32 lg:px-8" aria-labelledby="faq-title">
            <div class="mx-auto max-w-3xl">
                <div class="mb-14 text-center" data-reveal>
                    <p class="text-[13px] font-medium uppercase tracking-[.18em] text-zinc-500">Dúvidas frequentes</p>
                    <h2 id="faq-title" class="title-orbitron mt-4 text-balance text-4xl leading-[1.1] md:text-6xl">Tudo para começar com segurança.</h2>
                </div>
                <div class="overflow-hidden rounded-[32px] border border-white/[.08] bg-[#0c0c0c]">
                    @foreach ($faqs as $i => [$question, $answer])
                        <details class="group border-white/[.06] px-8 {{ $i > 0 ? 'border-t' : '' }}" data-reveal>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-6 text-[16px] font-medium text-white">
                                {{ $question }}
                                <span class="grid size-6 shrink-0 place-items-center rounded-full border border-white/15 text-zinc-400 transition group-open:rotate-45 group-open:border-yellow-300/40 group-open:text-yellow-300" aria-hidden="true">+</span>
                            </summary>
                            <p class="pb-6 text-[15px] leading-7 text-zinc-400">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA FINAL --}}
        <section id="cta-final" class="px-6 py-32 lg:px-8" aria-labelledby="final-title">
            <div class="mx-auto max-w-6xl">
                <div class="relative overflow-hidden rounded-[40px] border border-white/[.08] bg-gradient-to-b from-[#151515] to-[#080808] px-8 py-24 text-center md:px-16" data-reveal>
                    <div class="pointer-events-none absolute left-1/2 top-0 size-[50vw] max-w-[700px] -translate-x-1/2 rounded-full bg-[radial-gradient(circle,rgba(250,204,21,.14),transparent_60%)] blur-3xl"></div>
                    <h2 id="final-title" class="title-orbitron relative mx-auto max-w-3xl text-balance text-4xl leading-[1.1] md:text-6xl">Comece agora. Enquanto você lê isto, alguém já respondeu seu cliente.</h2>
                    <p class="relative mx-auto mt-6 max-w-xl text-lg leading-8 text-zinc-400">Crie sua conta em minutos, ative o atendimento com IA e coloque sua estética automotiva no automático. {{ $trialDays }} dias grátis, sem cartão e sem vendedor.</p>
                    <div class="relative mt-10 flex flex-col items-center justify-center gap-x-8 gap-y-4 sm:flex-row">
                        <a href="{{ $primaryCta }}" class="w-full rounded-full bg-yellow-300 px-8 py-3.5 text-center text-[15px] font-semibold text-black transition hover:bg-yellow-200 sm:w-auto">
                            Criar minha conta grátis
                        </a>
                        <a href="#planos" class="inline-flex items-center gap-1.5 text-[15px] font-medium text-white transition hover:text-yellow-200">
                            Comparar planos
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-white/[.06] px-6 py-16 lg:px-8">
            <div class="mx-auto flex max-w-6xl flex-col gap-12 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-sm">
                    <img src="{{ asset('img/logo-vertical.png') }}" alt="GarageON" class="h-8 w-auto">
                    <p class="mt-5 text-sm leading-6 text-zinc-500">O sistema operacional inteligente para empresas de estética automotiva. Atenda, agende, venda e cresça — sempre ON.</p>
                </div>
                <div class="grid grid-cols-2 gap-10 text-sm sm:grid-cols-3">
                    <div>
                        <p class="text-[13px] font-semibold text-white">Produto</p>
                        <ul class="mt-4 space-y-3 text-zinc-500">
                            <li><a href="#solucao" class="transition hover:text-white">Recursos</a></li>
                            <li><a href="#como-funciona" class="transition hover:text-white">Como funciona</a></li>
                            <li><a href="#planos" class="transition hover:text-white">Planos</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-[13px] font-semibold text-white">Comece</p>
                        <ul class="mt-4 space-y-3 text-zinc-500">
                            <li><a href="{{ $primaryCta }}" class="transition hover:text-white">Criar conta grátis</a></li>
                            <li><a href="{{ $loginUrl }}" class="transition hover:text-white">Entrar</a></li>
                            <li><a href="#faq" class="transition hover:text-white">Dúvidas frequentes</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-[13px] font-semibold text-white">GarageON</p>
                        <ul class="mt-4 space-y-3 text-zinc-500">
                            <li>Sempre ON, 24h por dia</li>
                            <li>100% na nuvem</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mx-auto mt-14 flex max-w-6xl flex-col gap-3 border-t border-white/[.06] pt-8 text-[13px] text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} GarageON. Todos os direitos reservados.</p>
                <p>Feito para quem cuida de carros com padrão premium.</p>
            </div>
        </footer>
    </main>
</body>
</html>
