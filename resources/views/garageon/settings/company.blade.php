<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurações da empresa - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>

        <div class="relative mx-auto max-w-5xl">
            @include('garageon.settings.nav')

            <section class="mt-6 rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                <div class="border-b border-white/10 pb-5">
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Dados da empresa</p>
                    <h2 class="mt-2 text-2xl font-black">Identidade operacional</h2>
                    <p class="mt-2 text-sm text-zinc-400">Essas informações aparecem no cockpit, agenda pública e comunicações com clientes.</p>
                </div>

                <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="mt-6 grid gap-5">
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

                        <label class="block md:col-span-2">
                            <span class="text-sm font-bold text-zinc-200">Domínio principal</span>
                            <input name="primary_domain" value="{{ old('primary_domain', $tenant->primary_domain) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('primary_domain') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
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
