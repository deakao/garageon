<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Landing Page - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    @php
        $defaultTestimonials = [
            ['name' => '', 'role' => '', 'quote' => '', 'rating' => 5],
        ];
        $landingValues = [
            'eyebrow' => old('eyebrow', $landingPage?->eyebrow ?? 'Estética automotiva premium'),
            'headline' => old('headline', $landingPage?->headline ?? 'Bem vindos à '.$tenant->name),
            'subheadline' => old('subheadline', $landingPage?->subheadline ?? 'Cada veículo recebe tratamento individualizado, deixando seu veículo novo de novo.'),
            'hero_image' => old('hero_image', $landingPage?->hero_image ?? 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1400&q=80'),
            'hero_badge_title' => old('hero_badge_title', $landingPage?->hero_badge_title ?? $tenant->name),
            'hero_badge_body' => old('hero_badge_body', $landingPage?->hero_badge_body ?? 'Detail premium com padrão de entrega visual.'),
            'cta_label' => old('cta_label', $landingPage?->cta_label ?? 'Orçamento Grátis'),
            'seo_title' => old('seo_title', $landingPage?->seo_title ?? ''),
            'seo_description' => old('seo_description', $landingPage?->seo_description ?? ''),
            'seo_keywords' => old('seo_keywords', $landingPage?->seo_keywords ?? ''),
            'analytics_head' => old('analytics_head', $landingPage?->analytics_head ?? ''),
            'conversion_pixel' => old('conversion_pixel', $landingPage?->conversion_pixel ?? ''),
            'custom_javascript' => old('custom_javascript', $landingPage?->custom_javascript ?? ''),
            'google_place_id' => old('google_place_id', $landingPage?->google_place_id ?? ''),
        ];
        $testimonials = collect(old('testimonials', $landingPage?->testimonials ?: $defaultTestimonials))
            ->map(fn ($item) => [
                'name' => (string) ($item['name'] ?? ''),
                'role' => (string) ($item['role'] ?? ''),
                'quote' => (string) ($item['quote'] ?? ''),
                'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
            ])
            ->values();
        if ($testimonials->isEmpty()) {
            $testimonials = collect($defaultTestimonials);
        }
        $activeServicesCount = $tenant->services->where('is_active', true)->count();
        $categoryCount = $tenant->serviceCategories->count();
        $filledTestimonialsCount = $testimonials->filter(fn ($item) => filled($item['name']) && filled($item['quote']))->count();
    @endphp

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

            <section class="mt-8 grid gap-6 xl:grid-cols-[.82fr_1.18fr]">
                <aside class="grid gap-6">
                    <article class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Editor visual</p>
                        <h1 class="mt-2 text-3xl font-black">Página de venda da loja</h1>
                        <p class="mt-3 text-sm leading-6 text-zinc-400">Ajuste promessa, SEO e pixels em um cockpit único. O preview responde enquanto você digita.</p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">{{ number_format($categoryCount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">categorias na vitrine</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">{{ number_format($filledTestimonialsCount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">depoimentos</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">SEO</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">title, description, keywords</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">PIXEL</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">head, conversão e JS</p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl border border-white/10 bg-black/35 p-4">
                            <p class="text-sm font-bold text-zinc-200">Link público</p>
                            <a href="{{ route('storefront', $tenant) }}" target="_blank" class="mt-2 inline-flex cursor-pointer break-all text-sm font-black text-yellow-300 transition hover:text-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                {{ route('storefront', $tenant) }}
                            </a>
                        </div>
                    </article>

                    <article class="sticky top-6 overflow-hidden rounded-[28px] border border-white/10 bg-zinc-950 shadow-2xl shadow-black/40">
                        <div class="border-b border-white/10 bg-white/[.04] px-5 py-4">
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Preview ao vivo</p>
                            <p class="mt-1 text-xs text-zinc-500">Simulação do template no estilo Box Detail.</p>
                        </div>
                        <div class="p-5">
                            <div class="overflow-hidden rounded-[24px] border border-white/10 bg-black">
                                <div class="flex items-center justify-between bg-yellow-300 px-4 py-2 text-black">
                                    <span class="font-orbitron text-xs font-black">{{ $tenant->name }}</span>
                                    <span class="text-[10px] font-black">Home · Serviços · Contato</span>
                                </div>
                                <div class="grid md:grid-cols-2">
                                    <div class="p-5">
                                        <p data-preview="eyebrow" class="text-[10px] font-bold text-white">{{ $landingValues['eyebrow'] }}</p>
                                        <h2 data-preview="headline" class="mt-4 text-2xl font-black leading-tight text-yellow-300">{{ $landingValues['headline'] }}</h2>
                                        <p data-preview="subheadline" class="mt-4 text-xs leading-5 text-zinc-200">{{ $landingValues['subheadline'] }}</p>
                                        <span data-preview="cta_label" class="mt-5 inline-flex rounded-lg bg-yellow-300 px-4 py-2 text-xs font-black text-black">{{ $landingValues['cta_label'] }}</span>
                                    </div>
                                    <div class="relative min-h-56 bg-cover bg-center" data-preview-image style="background-image: url('{{ $landingValues['hero_image'] }}')">
                                        <div class="absolute inset-0 bg-black/20"></div>
                                        <div class="absolute bottom-4 left-4 rounded-xl bg-black/70 px-3 py-2">
                                            <p data-preview="hero_badge_title" class="font-orbitron text-xs font-black text-yellow-300">{{ $landingValues['hero_badge_title'] }}</p>
                                            <p data-preview="hero_badge_body" class="mt-1 text-[10px] text-zinc-300">{{ $landingValues['hero_badge_body'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </aside>

                <form method="POST" action="{{ route('settings.landing.update') }}" enctype="multipart/form-data" class="grid gap-6" data-landing-editor>
                    @csrf
                    @method('PUT')

                    <section class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        <div class="mb-6 rounded-3xl border border-yellow-300/20 bg-yellow-300/[.06] p-5">
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Perfil da Empresa no Google</p>
                            <h2 class="mt-2 text-xl font-black">Avaliações reais, direto do Google Maps</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-400">Informe o Place ID da loja. Quando a integração estiver ativa, a landing mostra até 5 avaliações relevantes do Google e mantém os depoimentos manuais abaixo como fallback.</p>

                            <label class="mt-4 block">
                                <span class="text-sm font-bold text-zinc-200">Google Place ID</span>
                                <input name="google_place_id" value="{{ $landingValues['google_place_id'] }}" maxlength="255" placeholder="Ex.: ChIJN1t_tDeuEmsRUsoyG83frY4" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                <span class="mt-2 block text-xs leading-5 text-zinc-500">Encontre o código no <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noopener noreferrer" class="cursor-pointer font-bold text-yellow-300 hover:text-yellow-100">localizador oficial do Google</a>. A chave da Places API é configurada uma vez pela plataforma.</span>
                                @error('google_place_id') <span class="mt-2 block text-xs text-red-300">Use apenas letras, números, hífen e sublinhado no Place ID.</span> @enderror
                            </label>

                            @unless (config('services.google_places.api_key'))
                                <p class="mt-4 rounded-2xl border border-amber-300/20 bg-black/30 px-4 py-3 text-xs font-bold text-amber-100">A integração ficará pronta após configurar <code>GOOGLE_PLACES_API_KEY</code> no ambiente da plataforma.</p>
                            @endunless
                        </div>

                        <div class="flex flex-col gap-3 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Hero</p>
                                <h2 class="mt-2 text-2xl font-black">Primeira dobra da página</h2>
                                <p class="mt-1 text-sm text-zinc-400">A promessa precisa ficar clara antes do cliente rolar.</p>
                            </div>
                            <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
                                <input type="checkbox" name="published" value="1" @checked(old('published', (bool) ($landingPage?->published_at))) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
                                Publicar landing page
                            </label>
                        </div>

                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Selo acima do título</span>
                                <input name="eyebrow" value="{{ $landingValues['eyebrow'] }}" maxlength="80" data-preview-source="eyebrow" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('eyebrow') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Headline principal</span>
                                <input name="headline" value="{{ $landingValues['headline'] }}" required maxlength="255" data-preview-source="headline" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('headline') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Subtítulo</span>
                                <textarea name="subheadline" rows="3" required maxlength="255" data-preview-source="subheadline" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $landingValues['subheadline'] }}</textarea>
                                @error('subheadline') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <div class="grid gap-4 rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-4 md:col-span-2 md:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-bold text-zinc-200">Upload da imagem principal</span>
                                    <input type="file" name="hero_image_file" accept="image/png,image/jpeg,image/webp" data-preview-file="hero_image" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-zinc-300 file:mr-4 file:rounded-full file:border-0 file:bg-yellow-300 file:px-4 file:py-2 file:text-sm file:font-black file:text-black hover:file:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300/30">
                                    <span class="mt-2 block text-xs leading-5 text-zinc-500">Envie JPG, PNG ou WebP até 2 MB. Foto horizontal funciona melhor.</span>
                                    @error('hero_image_file') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                </label>

                                <label class="block">
                                    <span class="text-sm font-bold text-zinc-200">Ou usar imagem por URL</span>
                                    <input name="hero_image" value="{{ $landingValues['hero_image'] }}" type="text" maxlength="2048" data-preview-source="hero_image" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    <span class="mt-2 block text-xs text-zinc-500">Se enviar arquivo, ele substitui essa URL ao salvar.</span>
                                    @error('hero_image') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Texto do botão principal</span>
                                <input name="cta_label" value="{{ $landingValues['cta_label'] }}" required maxlength="80" data-preview-source="cta_label" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('cta_label') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Título do card visual</span>
                                <input name="hero_badge_title" value="{{ $landingValues['hero_badge_title'] }}" maxlength="80" data-preview-source="hero_badge_title" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('hero_badge_title') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Descrição do card visual</span>
                                <input name="hero_badge_body" value="{{ $landingValues['hero_badge_body'] }}" maxlength="160" data-preview-source="hero_badge_body" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('hero_badge_body') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-yellow-300/20 bg-yellow-300/10 p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Categorias e serviços</p>
                        <h2 class="mt-2 text-2xl font-black">A vitrine usa o catálogo da loja</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-300">A landing page agora monta as seções a partir das categorias cadastradas em Serviços, exibindo apenas serviços ativos.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">{{ number_format($activeServicesCount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">serviços ativos publicados</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="font-orbitron text-2xl font-black text-yellow-300">{{ number_format($categoryCount, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs font-bold text-zinc-400">categorias disponíveis</p>
                            </div>
                        </div>
                        <a href="{{ route('settings.services') }}" class="mt-5 inline-flex cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Gerenciar serviços
                        </a>
                    </section>

                    <section class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        <div class="flex flex-col gap-3 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Depoimentos</p>
                                <h2 class="mt-2 text-2xl font-black">Prova social na landing</h2>
                                <p class="mt-1 text-sm text-zinc-400">Cadastre depoimentos manuais para usar quando o Google não estiver configurado ou disponível.</p>
                            </div>
                            <button type="button" data-testimonial-add class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-yellow-300/30 bg-yellow-300/10 px-4 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                Adicionar depoimento
                            </button>
                        </div>

                        <div class="mt-5 grid gap-4" data-testimonials-list>
                            @foreach ($testimonials as $index => $testimonial)
                                <article class="rounded-3xl border border-white/10 bg-black/35 p-5" data-testimonial-item>
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="font-orbitron text-xs font-black uppercase tracking-[.18em] text-zinc-500">Depoimento <span data-testimonial-index>{{ $index + 1 }}</span></p>
                                        <button type="button" data-testimonial-remove class="cursor-pointer rounded-xl border border-white/10 px-3 py-2 text-xs font-bold text-zinc-400 transition hover:border-red-300/40 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-300/40">
                                            Remover
                                        </button>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <label class="block">
                                            <span class="text-sm font-bold text-zinc-200">Nome do cliente</span>
                                            <input name="testimonials[{{ $index }}][name]" value="{{ $testimonial['name'] }}" maxlength="80" placeholder="Ex.: Marcos T." class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                            @error('testimonials.'.$index.'.name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-bold text-zinc-200">Cargo ou contexto</span>
                                            <input name="testimonials[{{ $index }}][role]" value="{{ $testimonial['role'] }}" maxlength="80" placeholder="Ex.: Cliente desde 2023" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                            @error('testimonials.'.$index.'.role') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                        </label>

                                        <label class="block md:col-span-2">
                                            <span class="text-sm font-bold text-zinc-200">Depoimento</span>
                                            <textarea name="testimonials[{{ $index }}][quote]" rows="3" maxlength="500" placeholder="O que o cliente falou sobre o atendimento ou o resultado." class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $testimonial['quote'] }}</textarea>
                                            @error('testimonials.'.$index.'.quote') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                        </label>

                                        <label class="block md:col-span-2">
                                            <span class="text-sm font-bold text-zinc-200">Nota</span>
                                            <select name="testimonials[{{ $index }}][rating]" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                                @for ($rating = 5; $rating >= 1; $rating--)
                                                    <option value="{{ $rating }}" @selected((int) $testimonial['rating'] === $rating)>{{ $rating }} estrela{{ $rating > 1 ? 's' : '' }}</option>
                                                @endfor
                                            </select>
                                            @error('testimonials.'.$index.'.rating') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                        </label>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <template data-testimonial-template>
                            <article class="rounded-3xl border border-white/10 bg-black/35 p-5" data-testimonial-item>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-orbitron text-xs font-black uppercase tracking-[.18em] text-zinc-500">Depoimento <span data-testimonial-index>1</span></p>
                                    <button type="button" data-testimonial-remove class="cursor-pointer rounded-xl border border-white/10 px-3 py-2 text-xs font-bold text-zinc-400 transition hover:border-red-300/40 hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-300/40">
                                        Remover
                                    </button>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <label class="block">
                                        <span class="text-sm font-bold text-zinc-200">Nome do cliente</span>
                                        <input data-field="name" maxlength="80" placeholder="Ex.: Marcos T." class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    </label>

                                    <label class="block">
                                        <span class="text-sm font-bold text-zinc-200">Cargo ou contexto</span>
                                        <input data-field="role" maxlength="80" placeholder="Ex.: Cliente desde 2023" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    </label>

                                    <label class="block md:col-span-2">
                                        <span class="text-sm font-bold text-zinc-200">Depoimento</span>
                                        <textarea data-field="quote" rows="3" maxlength="500" placeholder="O que o cliente falou sobre o atendimento ou o resultado." class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30"></textarea>
                                    </label>

                                    <label class="block md:col-span-2">
                                        <span class="text-sm font-bold text-zinc-200">Nota</span>
                                        <select data-field="rating" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                            <option value="5" selected>5 estrelas</option>
                                            <option value="4">4 estrelas</option>
                                            <option value="3">3 estrelas</option>
                                            <option value="2">2 estrelas</option>
                                            <option value="1">1 estrela</option>
                                        </select>
                                    </label>
                                </div>
                            </article>
                        </template>
                    </section>

                    <section class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">SEO</p>
                        <h2 class="mt-2 text-2xl font-black">Como o Google e as redes enxergam a página</h2>
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Título SEO</span>
                                <input name="seo_title" value="{{ $landingValues['seo_title'] }}" maxlength="70" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                <span class="mt-2 block text-xs text-zinc-500">Até 70 caracteres. Se ficar vazio, usa o nome da loja.</span>
                                @error('seo_title') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Descrição SEO</span>
                                <textarea name="seo_description" rows="3" maxlength="160" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $landingValues['seo_description'] }}</textarea>
                                <span class="mt-2 block text-xs text-zinc-500">Até 160 caracteres. Aparece em buscadores e previews sociais.</span>
                                @error('seo_description') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-zinc-200">Palavras-chave</span>
                                <input name="seo_keywords" value="{{ $landingValues['seo_keywords'] }}" maxlength="255" placeholder="vitrificação, lavagem técnica, estética automotiva" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                @error('seo_keywords') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.24em] text-yellow-300">Rastreamento</p>
                        <h2 class="mt-2 text-2xl font-black">Analytics, pixel e JavaScript personalizado</h2>
                        <p class="mt-2 text-sm leading-6 text-zinc-400">Cole tags fornecidas por Google, Meta ou outras ferramentas. Esses códigos serão publicados somente na landing page pública desta loja.</p>

                        <div class="mt-5 grid gap-5">
                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Analytics no head</span>
                                <textarea name="analytics_head" rows="5" maxlength="10000" spellcheck="false" placeholder="Cole aqui tags como Google Analytics ou Google Tag Manager" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-mono text-xs text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $landingValues['analytics_head'] }}</textarea>
                                @error('analytics_head') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Pixel de conversão</span>
                                <textarea name="conversion_pixel" rows="5" maxlength="10000" spellcheck="false" placeholder="Cole aqui pixels e noscript de conversão" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-mono text-xs text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $landingValues['conversion_pixel'] }}</textarea>
                                @error('conversion_pixel') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">JavaScript personalizado antes do fechamento do body</span>
                                <textarea name="custom_javascript" rows="6" maxlength="20000" spellcheck="false" placeholder="Cole aqui scripts adicionais, widgets ou eventos personalizados" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-mono text-xs text-white outline-none placeholder:text-zinc-600 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $landingValues['custom_javascript'] }}</textarea>
                                @error('custom_javascript') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </section>

                    <div class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-[24px] border border-white/10 bg-black/85 p-4 shadow-2xl shadow-black/50 backdrop-blur sm:flex-row sm:justify-end">
                        <a href="{{ route('storefront', $tenant) }}" target="_blank" class="cursor-pointer rounded-2xl border border-white/10 px-5 py-3 text-center text-sm font-bold text-zinc-200 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300">Ver página</a>
                        <button class="cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar landing</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script>
        document.querySelectorAll('[data-preview-source]').forEach((field) => {
            const target = document.querySelector(`[data-preview="${field.dataset.previewSource}"]`);

            if (field.dataset.previewSource === 'hero_image') {
                const imageTarget = document.querySelector('[data-preview-image]');

                field.addEventListener('input', () => {
                    imageTarget.style.backgroundImage = field.value ? `url('${field.value}')` : 'none';
                });

                return;
            }

            if (!target) {
                return;
            }

            field.addEventListener('input', () => {
                target.textContent = field.value || '...';
            });
        });

        document.querySelector('[data-preview-file="hero_image"]')?.addEventListener('change', (event) => {
            const image = event.target.files?.[0];
            const imageTarget = document.querySelector('[data-preview-image]');

            if (image && imageTarget) {
                imageTarget.style.backgroundImage = `url('${URL.createObjectURL(image)}')`;
            }
        });

        (() => {
            const list = document.querySelector('[data-testimonials-list]');
            const template = document.querySelector('[data-testimonial-template]');
            const addButton = document.querySelector('[data-testimonial-add]');
            const maxItems = 12;

            if (!list || !template || !addButton) {
                return;
            }

            const reindex = () => {
                list.querySelectorAll('[data-testimonial-item]').forEach((item, index) => {
                    item.querySelector('[data-testimonial-index]').textContent = String(index + 1);

                    item.querySelectorAll('[data-field], [name]').forEach((field) => {
                        const key = field.dataset.field || (field.getAttribute('name') || '').match(/\[([^\]]+)\]$/)?.[1];

                        if (!key) {
                            return;
                        }

                        field.setAttribute('name', `testimonials[${index}][${key}]`);
                    });
                });
            };

            const bindRemove = (item) => {
                item.querySelector('[data-testimonial-remove]')?.addEventListener('click', () => {
                    if (list.querySelectorAll('[data-testimonial-item]').length <= 1) {
                        item.querySelectorAll('input, textarea').forEach((field) => {
                            field.value = '';
                        });
                        const rating = item.querySelector('select');
                        if (rating) {
                            rating.value = '5';
                        }
                        return;
                    }

                    item.remove();
                    reindex();
                });
            };

            list.querySelectorAll('[data-testimonial-item]').forEach(bindRemove);

            addButton.addEventListener('click', () => {
                if (list.querySelectorAll('[data-testimonial-item]').length >= maxItems) {
                    return;
                }

                const item = template.content.firstElementChild.cloneNode(true);
                list.appendChild(item);
                bindRemove(item);
                reindex();
                item.querySelector('input')?.focus();
            });

            reindex();
        })();
    </script>
</body>
</html>
