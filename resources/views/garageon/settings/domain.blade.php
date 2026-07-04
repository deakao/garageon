<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Domínio - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    @php
        $configuredDomain = old('primary_domain', $tenant->primary_domain);
        $cnameName = $configuredDomain ? \Illuminate\Support\Str::before($configuredDomain, '.') : 'www';
        $isConfigured = filled($tenant->primary_domain);
    @endphp

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

            <section class="mt-8 grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
                <aside class="grid gap-6">
                    <article class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Domínio próprio</p>
                        <h1 class="mt-2 text-3xl font-black">Sua landing no endereço da loja</h1>
                        <p class="mt-3 text-sm leading-6 text-zinc-400">Conecte um domínio ou subdomínio para o cliente entrar direto na página pública da {{ $tenant->name }}.</p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black {{ $isConfigured ? 'text-yellow-300' : 'text-zinc-500' }}">{{ $isConfigured ? 'ON' : 'OFF' }}</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">status do domínio</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">CNAME</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">tipo de registro recomendado</p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-white/10 bg-black/35 p-4">
                            <p class="text-sm font-bold text-zinc-200">Link atual da landing</p>
                            @if ($customDomainUrl)
                                <a href="{{ $customDomainUrl }}" target="_blank" class="mt-2 inline-flex cursor-pointer break-all text-sm font-black text-yellow-300 transition hover:text-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-300">{{ $customDomainUrl }}</a>
                            @else
                                <a href="{{ route('storefront', $tenant) }}" target="_blank" class="mt-2 inline-flex cursor-pointer break-all text-sm font-black text-yellow-300 transition hover:text-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-300">{{ route('storefront', $tenant) }}</a>
                            @endif
                        </div>
                    </article>

                    <article class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Antes de começar</p>
                        <h2 class="mt-2 text-2xl font-black">Use de preferência o subdomínio www</h2>
                        <p class="mt-3 text-sm leading-6 text-zinc-400">A maioria dos provedores aceita CNAME em `www`. Para o domínio sem `www`, alguns provedores exigem redirecionamento para `www`.</p>
                    </article>
                </aside>

                <div class="grid gap-6">
                    <form method="POST" action="{{ route('settings.domain.update') }}" class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        @csrf
                        @method('PUT')

                        <div class="border-b border-white/10 pb-5">
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Configuração</p>
                            <h2 class="mt-2 text-2xl font-black">Domínio da landing</h2>
                            <p class="mt-1 text-sm text-zinc-400">Informe o domínio que vai apontar para a GarageON. Não use `https://` nem caminho.</p>
                        </div>

                        <label class="mt-5 block">
                            <span class="text-sm font-bold text-zinc-200">Domínio ou subdomínio</span>
                            <input name="primary_domain" value="{{ $configuredDomain }}" placeholder="www.sualoja.com.br" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            <span class="mt-2 block text-xs leading-5 text-zinc-500">Exemplo recomendado: `www.sualoja.com.br`. Deixe vazio para remover o domínio próprio.</span>
                            @error('primary_domain') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <a href="{{ route('settings.landing') }}" class="cursor-pointer rounded-2xl border border-white/10 px-5 py-3 text-center text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Editar landing</a>
                            <button class="cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar domínio</button>
                        </div>
                    </form>

                    <section class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Passo a passo CNAME</p>
                        <h2 class="mt-2 text-2xl font-black">O que o cliente deve fazer no provedor do domínio</h2>

                        <div class="mt-6 grid gap-4">
                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <div class="flex items-start gap-4">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-yellow-300 font-orbitron text-sm font-black text-black">1</span>
                                    <div>
                                        <h3 class="font-black text-white">Entrar no painel DNS</h3>
                                        <p class="mt-1 text-sm leading-6 text-zinc-400">Acesse onde o domínio foi comprado, como Registro.br, GoDaddy, Hostinger, Cloudflare ou similar.</p>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <div class="flex items-start gap-4">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-yellow-300 font-orbitron text-sm font-black text-black">2</span>
                                    <div>
                                        <h3 class="font-black text-white">Criar um registro CNAME</h3>
                                        <p class="mt-1 text-sm leading-6 text-zinc-400">Crie ou edite o registro com estes dados:</p>
                                        <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                            <div class="rounded-xl border border-white/10 bg-white/[.04] p-3">
                                                <span class="block text-xs font-black uppercase tracking-[.12em] text-zinc-500">Tipo</span>
                                                <strong class="mt-1 block font-orbitron text-yellow-300">CNAME</strong>
                                            </div>
                                            <div class="rounded-xl border border-white/10 bg-white/[.04] p-3">
                                                <span class="block text-xs font-black uppercase tracking-[.12em] text-zinc-500">Nome</span>
                                                <strong class="mt-1 block break-all font-orbitron text-yellow-300">{{ $cnameName }}</strong>
                                            </div>
                                            <div class="rounded-xl border border-white/10 bg-white/[.04] p-3">
                                                <span class="block text-xs font-black uppercase tracking-[.12em] text-zinc-500">Destino</span>
                                                <strong class="mt-1 block break-all font-orbitron text-yellow-300">{{ $platformHost }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <div class="flex items-start gap-4">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-yellow-300 font-orbitron text-sm font-black text-black">3</span>
                                    <div>
                                        <h3 class="font-black text-white">Salvar e aguardar propagação</h3>
                                        <p class="mt-1 text-sm leading-6 text-zinc-400">A atualização costuma levar alguns minutos, mas pode demorar até 24 horas dependendo do provedor.</p>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <div class="flex items-start gap-4">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-yellow-300 font-orbitron text-sm font-black text-black">4</span>
                                    <div>
                                        <h3 class="font-black text-white">Testar no navegador</h3>
                                        <p class="mt-1 text-sm leading-6 text-zinc-400">Quando o DNS propagar, acessar o domínio configurado cairá direto na landing page da loja.</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
