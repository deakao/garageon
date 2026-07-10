<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Empresa - {{ $tenant->name }}</title>
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

            <section class="mt-6 overflow-hidden rounded-[32px] border border-white/10 bg-[#101010]/95 shadow-2xl shadow-black/30 backdrop-blur">
                <div class="grid divide-y divide-white/10 lg:grid-cols-3 lg:divide-x lg:divide-y-0 lg:divide-white/10">
                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-building-store class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Plano atual</p>
                            <strong class="mt-1 block font-orbitron text-2xl font-black text-white">{{ $tenant->plan?->name ?? 'Operacional' }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">{{ $tenant->slug }}</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-users-group class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Equipe</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($companyStats['team'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">{{ $companyStats['team'] === 1 ? 'membro ativo' : 'membros ativos' }}</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-users class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Clientes na base</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($companyStats['customers'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">{{ number_format($companyStats['services'], 0, ',', '.') }} serviços cadastrados</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 border-b border-white/10 pb-5">
                    <h1 class="font-orbitron text-2xl font-black text-white">Empresa</h1>
                    <p class="mt-1 text-sm text-zinc-400">Essas informações aparecem no cockpit, agenda pública e comunicações com clientes.</p>
                </div>

                <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="grid gap-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 rounded-2xl border border-white/10 bg-black/35 p-4 md:grid-cols-[160px_1fr] md:items-center">
                        <div class="grid h-28 w-full place-items-center rounded-2xl border border-yellow-300/20 bg-white/[.04] p-4 md:w-36">
                            @if ($tenant->logoUrl())
                                <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="max-h-20 max-w-full object-contain">
                            @else
                                <span class="font-orbitron text-xs font-black uppercase tracking-[.22em] text-yellow-300">Sem logo</span>
                            @endif
                        </div>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Logo da loja</span>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-zinc-300 file:mr-4 file:rounded-full file:border-0 file:bg-yellow-300 file:px-4 file:py-2 file:text-sm file:font-black file:text-black hover:file:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300/30">
                            <span class="mt-2 block text-xs leading-5 text-zinc-500">Envie PNG, JPG ou WebP com até 2 MB. Use fundo transparente quando possível.</span>
                            @error('logo') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Nome da empresa</span>
                            <input name="name" value="{{ old('name', $tenant->name) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Razão social</span>
                            <input name="legal_name" value="{{ old('legal_name', $tenant->legal_name) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('legal_name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Documento</span>
                            <input name="document" value="{{ old('document', $tenant->document) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('document') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">WhatsApp principal</span>
                            <input name="whatsapp_phone" value="{{ old('whatsapp_phone', $tenant->whatsapp_phone) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('whatsapp_phone') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <div class="rounded-2xl border border-yellow-300/20 bg-yellow-300/10 p-4 md:col-span-2">
                            <p class="text-sm font-black text-yellow-200">Domínio próprio da landing</p>
                            <p class="mt-1 text-sm leading-6 text-zinc-300">Configure em uma tela separada com o passo a passo de CNAME para apontar sua landing para o endereço da loja.</p>
                            <a href="{{ route('settings.domain') }}" class="mt-3 inline-flex cursor-pointer rounded-xl border border-yellow-300/30 px-4 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">Configurar domínio</a>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-white/10 pt-5">
                        <button class="rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar empresa</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
