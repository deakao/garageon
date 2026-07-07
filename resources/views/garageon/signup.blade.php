<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro - GarageON</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B0B0B] text-white antialiased">
    <main class="relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_12%,rgba(255,196,0,.16),transparent_32%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_42%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:48px_48px]"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-yellow-300 to-transparent"></div>

        <section class="relative w-full max-w-lg rounded-[24px] border border-white/10 bg-black/80 p-6 shadow-2xl shadow-black/70 backdrop-blur sm:p-8">
            <a href="{{ route('home') }}" class="mx-auto flex w-fit justify-center">
                <img
                    src="{{ asset('img/logo-vertical.png') }}"
                    alt="GarageON"
                    class="h-24 w-auto"
                >
            </a>

            <div class="mt-8 text-center">
                <p class="text-xs font-black uppercase tracking-[.28em] text-yellow-300">Empresa Sempre ON</p>
                <h1 class="mt-3 font-orbitron text-2xl font-black tracking-tight text-white">Criar meu cadastro</h1>
                <p class="mt-2 text-sm leading-6 text-zinc-400">Conte um pouco sobre sua operação e defina sua senha para entrar no painel agora.</p>
            </div>

            @isset($selectedPlan)
                <div class="mt-6 flex items-center justify-between rounded-xl border border-yellow-300/30 bg-yellow-300/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.2em] text-yellow-300">Plano escolhido</p>
                        <p class="mt-1 font-orbitron text-lg font-black text-white">{{ $selectedPlan->name }}</p>
                    </div>
                    <span class="font-orbitron text-lg font-black text-yellow-200">R$ {{ number_format((float) $selectedPlan->monthly_price, 0, ',', '.') }}<span class="text-xs font-bold">/mês</span></span>
                </div>
            @endisset

            @if (session('status'))
                <p class="mt-6 rounded-xl border border-yellow-300/25 bg-yellow-300/10 px-4 py-3 text-sm leading-6 text-yellow-100">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('signup.store') }}" class="mt-8 space-y-5">
                @csrf
                @isset($selectedPlan)
                    <input type="hidden" name="plan" value="{{ $selectedPlan->slug }}">
                @endisset

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="owner_name" class="text-sm font-bold text-zinc-100">Seu nome</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <input
                                id="owner_name"
                                name="owner_name"
                                type="text"
                                value="{{ old('owner_name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Nome do responsável"
                                class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                            >
                        </div>
                        @error('owner_name')
                            <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Informe seu nome para continuarmos.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="business_name" class="text-sm font-bold text-zinc-100">Nome da empresa</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <input
                                id="business_name"
                                name="business_name"
                                type="text"
                                value="{{ old('business_name') }}"
                                required
                                autocomplete="organization"
                                placeholder="Ex: Carbon Detail"
                                class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                            >
                        </div>
                        @error('business_name')
                            <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Informe o nome da empresa.</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="text-sm font-bold text-zinc-100">E-mail</label>
                    <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="contato@empresa.com"
                            class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                        >
                    </div>
                    @error('email')
                        <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Informe um e-mail válido.</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="text-sm font-bold text-zinc-100">Senha</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Mínimo 8 caracteres"
                                class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                            >
                        </div>
                        @error('password')
                            <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Use uma senha com pelo menos 8 caracteres.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-bold text-zinc-100">Confirmar senha</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Repita sua senha"
                                class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                            >
                        </div>
                    </div>
                </div>

                <div>
                    <label for="whatsapp_phone" class="text-sm font-bold text-zinc-100">WhatsApp</label>
                    <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                        <input
                            id="whatsapp_phone"
                            name="whatsapp_phone"
                            type="tel"
                            value="{{ old('whatsapp_phone') }}"
                            required
                            autocomplete="tel"
                            inputmode="numeric"
                            maxlength="15"
                            data-phone-mask
                            placeholder="(11) 99999-9999"
                            class="w-full rounded-xl bg-transparent px-4 py-3.5 text-white outline-none placeholder:text-zinc-600"
                        >
                    </div>
                    @error('whatsapp_phone')
                        <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Informe um WhatsApp para falarmos com você.</p>
                    @enderror
                </div>

                <div>
                    <label for="business_type" class="text-sm font-bold text-zinc-100">Tipo de operação</label>
                    <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                        <select
                            id="business_type"
                            name="business_type"
                            required
                            class="w-full rounded-xl bg-[#111111] px-4 py-3.5 text-white outline-none"
                        >
                            <option value="">Selecione sua operação</option>
                            @foreach (['Estética automotiva', 'Detailing', 'Lava-rápido premium', 'Centro automotivo', 'Vitrificação', 'PPF', 'Envelopamento', 'Higienização', 'Polimento técnico'] as $type)
                                <option value="{{ $type }}" @selected(old('business_type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('business_type')
                        <p class="mt-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3 py-2 text-sm leading-5 text-red-200">Escolha o tipo da sua operação.</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="monthly_leads" class="text-sm font-bold text-zinc-100">Leads por mês</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <select id="monthly_leads" name="monthly_leads" class="w-full rounded-xl bg-[#111111] px-4 py-3.5 text-white outline-none">
                                <option value="">Ainda não sei</option>
                                @foreach (['Até 50', '51 a 150', '151 a 300', 'Mais de 300'] as $range)
                                    <option value="{{ $range }}" @selected(old('monthly_leads') === $range)>{{ $range }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="main_challenge" class="text-sm font-bold text-zinc-100">Principal desafio</label>
                        <div class="mt-2 rounded-xl border border-white/10 bg-[#111111] transition focus-within:border-yellow-300 focus-within:shadow-[0_0_0_4px_rgba(255,196,0,.10)]">
                            <select id="main_challenge" name="main_challenge" class="w-full rounded-xl bg-[#111111] px-4 py-3.5 text-white outline-none">
                                <option value="">Escolha se quiser</option>
                                @foreach (['Responder WhatsApp', 'Organizar agenda', 'Recuperar clientes', 'Fazer follow-up', 'Vender mais', 'Gerir a operação'] as $challenge)
                                    <option value="{{ $challenge }}" @selected(old('main_challenge') === $challenge)>{{ $challenge }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-[14px] bg-yellow-300 px-6 py-4 font-orbitron text-sm font-black uppercase tracking-[.22em] text-black transition hover:-translate-y-0.5 hover:shadow-[0_0_34px_rgba(255,196,0,.28)] focus:outline-none focus:ring-4 focus:ring-yellow-300/30">
                    Finalizar cadastro
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm font-bold text-zinc-300 transition hover:text-yellow-200 focus:outline-none focus:ring-4 focus:ring-yellow-300/20">Já tenho cadastro</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
