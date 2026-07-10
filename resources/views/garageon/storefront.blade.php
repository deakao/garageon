<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    @php
        $landingPage = $tenant->landingPage;
        $serviceImages = [
            'https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=900&q=80',
        ];
        $headline = $landingPage?->headline ?: 'Bem vindos à '.$tenant->name;
        $subheadline = $landingPage?->subheadline ?: 'Cada veículo recebe tratamento individualizado, deixando seu veículo novo de novo.';
        $eyebrow = $landingPage?->eyebrow ?: 'Conheça a sua nova estética automotiva';
        $ctaLabel = $landingPage?->cta_label ?: 'Orçamento Grátis';
        $heroBadgeTitle = $landingPage?->hero_badge_title ?: $tenant->name;
        $heroBadgeBody = $landingPage?->hero_badge_body ?: 'Detail premium com padrão de entrega visual.';
        $heroImageUrl = $landingPage?->hero_image ?: 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1400&q=80';
        $seoTitle = $landingPage?->seo_title ?: $tenant->name;
        $seoDescription = $landingPage?->seo_description ?: $subheadline;
        $activeServices = $tenant->services->where('is_active', true)->sortBy('name')->values();
        $categoryNames = $tenant->serviceCategories
            ->sortBy('name')
            ->pluck('name')
            ->merge($activeServices->pluck('category')->filter())
            ->unique()
            ->values();
        $serviceSections = $categoryNames->map(function (string $categoryName) use ($activeServices) {
            $normalizedCategoryName = \Illuminate\Support\Str::lower($categoryName);

            return [
                'id' => \Illuminate\Support\Str::slug($categoryName),
                'name' => $categoryName,
                'is_package' => str_contains($normalizedCategoryName, 'pacote'),
                'services' => $activeServices->where('category', $categoryName)->values(),
            ];
        })->filter(fn (array $section) => $section['services']->isNotEmpty())->values();
        $servicesAnchor = $serviceSections->first()['id'] ?? 'servicos';
        $testimonials = $landingPage?->publishedTestimonials() ?? [];
        $storeWhatsappPhone = \App\Support\WhatsappPhone::normalize($tenant->whatsapp_phone);
        $whatsappLeadAction = ($customDomain ?? false)
            ? route('storefront.custom.whatsapp-lead.store')
            : route('storefront.whatsapp-lead.store', $tenant);
        $firstBookableServiceId = array_key_first($bookingAvailability['services'] ?? []);
        $publicUrl = ($customDomain ?? false) ? url('/') : route('storefront', $tenant);
        $bookingFormAction = ($customDomain ?? false) ? route('storefront.custom.booking.store') : route('storefront.booking.store', $tenant);
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    @if ($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    @if ($landingPage?->seo_keywords)
        <meta name="keywords" content="{{ $landingPage->seo_keywords }}">
    @endif
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $publicUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! $landingPage?->analytics_head !!}
</head>
<body class="marketing bg-black text-white antialiased">
    {!! $landingPage?->conversion_pixel !!}

    @if (session('booking_status'))
        <div class="fixed left-1/2 top-5 z-50 w-[min(92vw,520px)] -translate-x-1/2 rounded-2xl border border-[#ffcc00]/40 bg-black/95 px-5 py-4 text-sm font-bold text-[#ffcc00] shadow-2xl shadow-black/60">
            {{ session('booking_status') }}
        </div>
    @endif

    <header class="sticky top-0 z-40 border-b border-black/20 bg-[#ffcc00] text-black shadow-[0_6px_20px_rgba(0,0,0,.28)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 lg:px-8">
            <a href="#home" class="flex cursor-pointer items-center gap-2 focus:outline-none focus:ring-2 focus:ring-black/40">
                @if ($tenant->logoUrl())
                    <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="h-8 max-w-40 object-contain">
                @else
                    <span class="font-orbitron text-base font-black leading-none tracking-tight">{{ $tenant->name }}</span>
                @endif
            </a>

            <nav class="hidden items-center gap-7 text-xs font-semibold md:flex">
                <a href="#home" class="cursor-pointer transition hover:text-white focus:outline-none focus:ring-2 focus:ring-black/40">Home</a>
                @foreach ($serviceSections->take(3) as $section)
                    <a href="#{{ $section['id'] }}" class="cursor-pointer transition hover:text-white focus:outline-none focus:ring-2 focus:ring-black/40">{{ $section['name'] }}</a>
                @endforeach
                @if (count($testimonials) > 0)
                    <a href="#depoimentos" class="cursor-pointer transition hover:text-white focus:outline-none focus:ring-2 focus:ring-black/40">Depoimentos</a>
                @endif
                <a href="#contato" class="cursor-pointer transition hover:text-white focus:outline-none focus:ring-2 focus:ring-black/40">Contato</a>
                <span aria-hidden="true" class="font-orbitron text-sm">◎</span>
            </nav>
        </div>
    </header>

    <main id="home">
        <section class="bg-black">
            <div class="mx-auto grid max-w-7xl lg:min-h-[455px] lg:grid-cols-[1.05fr_.95fr]">
                <div class="flex items-center px-6 py-16 lg:px-20 lg:py-20">
                    <div class="max-w-xl">
                        <p class="text-xs font-medium text-white">{{ $eyebrow }}</p>
                        <h1 class="title-orbitron mt-5 text-4xl leading-tight text-[#ffcc00] md:text-5xl">{{ $headline }}</h1>
                        <p class="mt-5 text-base font-normal leading-7 text-zinc-100">{{ $subheadline }}</p>

                        <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                            <a href="#{{ $servicesAnchor }}" class="inline-flex cursor-pointer items-center justify-center rounded-[10px] border-2 border-[#ffcc00] px-7 py-3 text-sm font-black text-[#ffcc00] transition hover:bg-[#ffcc00] hover:text-black focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">
                                Confira nossos serviços →
                            </a>
                            @if ($firstBookableServiceId)
                                <button type="button" data-booking-open="{{ $firstBookableServiceId }}" class="inline-flex cursor-pointer items-center justify-center rounded-[10px] bg-[#ffcc00] px-7 py-3 text-sm font-black text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">
                                    {{ $ctaLabel }} →
                                </button>
                            @else
                                <a href="{{ route('booking', $tenant) }}" class="inline-flex cursor-pointer items-center justify-center rounded-[10px] bg-[#ffcc00] px-7 py-3 text-sm font-black text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">
                                    {{ $ctaLabel }} →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="relative min-h-[330px] overflow-hidden bg-zinc-900 lg:min-h-full">
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImageUrl }}')"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,.55),transparent_38%),linear-gradient(180deg,transparent,rgba(0,0,0,.20))]"></div>
                    <div class="absolute left-8 top-10 rounded-xl bg-black/70 px-4 py-3 backdrop-blur">
                        <p class="font-orbitron text-sm font-black text-[#ffcc00]">{{ $heroBadgeTitle }}</p>
                        <p class="mt-1 max-w-44 text-xs font-normal leading-5 text-white/80">{{ $heroBadgeBody }}</p>
                    </div>
                </div>
            </div>
        </section>

        @forelse ($serviceSections as $section)
            <section id="{{ $section['id'] }}" class="{{ $section['is_package'] ? 'bg-[#242526]' : 'bg-black' }} px-6 py-16 lg:px-8">
                <div class="mx-auto {{ $section['is_package'] ? 'max-w-6xl' : 'max-w-7xl' }}">
                    <h2 class="title-orbitron text-center text-3xl {{ $section['is_package'] ? 'uppercase' : '' }} text-[#ffcc00]">{{ $section['is_package'] ? \Illuminate\Support\Str::upper($section['name']) : $section['name'] }}</h2>

                    @if ($section['is_package'])
                        <div class="mt-12 grid gap-8 md:grid-cols-3">
                            @foreach ($section['services'] as $service)
                                @php
                                    $packageItems = collect(preg_split('/\r\n|\r|\n|;/', (string) $service->description))
                                        ->map(fn ($item) => trim($item))
                                        ->filter()
                                        ->values();
                                @endphp
                                <article class="flex min-h-full flex-col rounded-md bg-black p-8 shadow-[0_18px_30px_rgba(0,0,0,.35)] ring-1 ring-white/5">
                                    <h3 class="title-orbitron text-center text-xl text-white">{{ $service->name }}</h3>
                                    <ul class="mt-6 grow space-y-3 text-sm font-normal leading-6 text-zinc-200">
                                        @forelse ($packageItems as $item)
                                            <li class="flex gap-2">
                                                <span class="mt-1 text-[#ffcc00]">✓</span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @empty
                                            <li class="flex gap-2">
                                                <span class="mt-1 text-[#ffcc00]">✓</span>
                                                <span>{{ $service->duration_minutes }} minutos de cuidado técnico.</span>
                                            </li>
                                        @endforelse
                                    </ul>
                                    <p class="mt-6 text-center text-sm font-black text-[#ffcc00]">R$ {{ number_format((float) $service->price, 2, ',', '.') }}</p>
                                    <button type="button" data-booking-open="{{ $service->id }}" class="mt-5 inline-flex w-full cursor-pointer items-center justify-center rounded bg-[#ffcc00] px-5 py-3 text-sm font-black text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">Saber mais</button>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($section['services'] as $service)
                                @php($image = $service->thumbnailUrl() ?: $serviceImages[max(0, $service->id - 1) % count($serviceImages)])
                                <article class="overflow-hidden rounded bg-[#202123] shadow-[0_16px_30px_rgba(0,0,0,.35)] ring-1 ring-white/5">
                                    <div class="h-44 bg-cover bg-center" style="background-image: url('{{ $image }}')"></div>
                                    <div class="p-5">
                                        <h3 class="title-orbitron min-h-12 text-base leading-6 text-[#ffcc00]">{{ $service->name }}</h3>
                                        <p class="mt-4 min-h-28 text-sm font-normal leading-6 text-zinc-300">{{ $service->description }}</p>
                                        <p class="mt-5 text-lg font-black text-white">R$ {{ number_format((float) $service->price, 2, ',', '.') }}</p>
                                        <button type="button" data-booking-open="{{ $service->id }}" class="mt-5 inline-flex cursor-pointer rounded-full bg-[#ffcc00] px-4 py-2 text-xs font-black text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">Agendar Serviço</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @empty
            <section id="servicos" class="bg-[#242526] px-6 py-16 lg:px-8">
                <div class="mx-auto max-w-6xl rounded-xl border border-[#ffcc00]/25 bg-[#202123] p-8 text-center text-zinc-300">
                    <p class="font-bold text-white">Sua vitrine ainda não possui serviços ativos.</p>
                    <p class="mt-2 text-sm">Cadastre categorias e serviços no cockpit para preencher esta área automaticamente.</p>
                </div>
            </section>
        @endforelse

        @if (count($testimonials) > 0)
            <section id="depoimentos" class="bg-[#242526] px-6 py-16 lg:px-8" aria-labelledby="testimonials-title">
                <div class="mx-auto max-w-6xl">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-xs font-medium uppercase tracking-[.24em] text-[#ffcc00]">Depoimentos</p>
                        <h2 id="testimonials-title" class="title-orbitron mt-4 text-3xl text-white md:text-4xl">O que nossos clientes dizem</h2>
                        <p class="mt-3 text-sm font-normal leading-6 text-zinc-300">Resultados reais de quem confiou o carro à {{ $tenant->name }}.</p>
                    </div>

                    <div class="mt-12 grid gap-6 md:grid-cols-2 {{ count($testimonials) > 2 ? 'lg:grid-cols-3' : '' }}">
                        @foreach ($testimonials as $testimonial)
                            <figure class="flex h-full flex-col rounded-md bg-black p-7 shadow-[0_18px_30px_rgba(0,0,0,.35)] ring-1 ring-white/5">
                                <div class="text-[#ffcc00]" aria-label="{{ $testimonial['rating'] }} de 5 estrelas">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span aria-hidden="true">{{ $star <= $testimonial['rating'] ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                                <blockquote class="mt-5 grow text-sm font-normal leading-7 text-zinc-200">"{{ $testimonial['quote'] }}"</blockquote>
                                <figcaption class="mt-6 border-t border-white/10 pt-5">
                                    <strong class="block text-sm font-semibold text-white">{{ $testimonial['name'] }}</strong>
                                    @if ($testimonial['role'])
                                        <span class="mt-1 block text-xs font-medium text-zinc-500">{{ $testimonial['role'] }}</span>
                                    @endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @if (! empty($bookingAvailability['services']))
        <dialog id="booking-modal" data-booking-modal data-open-on-error="{{ $errors->booking->any() ? '1' : '0' }}" data-old-service="{{ old('service_id', $firstBookableServiceId) }}" data-old-date="{{ old('scheduled_date') }}" data-old-time="{{ old('scheduled_time') }}" class="fixed inset-0 m-auto h-fit max-h-[92vh] w-[min(96vw,1120px)] overflow-hidden rounded-[28px] border border-[#ffcc00]/25 bg-white p-0 text-[#0b2b4c] shadow-2xl shadow-black/80 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
            <form method="POST" action="{{ $bookingFormAction }}" data-booking-form class="relative">
                @csrf
                <input type="hidden" name="service_id" value="{{ old('service_id', $firstBookableServiceId) }}" data-booking-service-input>
                <input type="hidden" name="scheduled_date" value="{{ old('scheduled_date') }}" data-booking-date-input>
                <input type="hidden" name="scheduled_time" value="{{ old('scheduled_time') }}" data-booking-time-input>

                <div class="grid max-h-[90vh] overflow-y-auto lg:grid-cols-[.85fr_1.28fr_.95fr]">
                    <aside class="border-b border-slate-200 bg-white p-6 lg:border-b-0 lg:border-r lg:p-8">
                        <button type="button" data-booking-close class="ml-auto grid h-10 w-10 cursor-pointer place-items-center rounded-full border border-slate-200 text-xl text-slate-500 transition hover:border-[#ffcc00] hover:text-black lg:hidden" aria-label="Fechar">×</button>

                        <div class="mt-2 flex items-center gap-3 lg:mt-0">
                            @if ($tenant->logoUrl())
                                <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="h-10 max-w-36 object-contain">
                            @else
                                <span class="font-orbitron text-lg font-black text-black">{{ $tenant->name }}</span>
                            @endif
                        </div>

                        <div class="mt-10 border-t border-slate-200 pt-8">
                            <p class="text-sm font-bold text-slate-500">Serviço selecionado</p>
                            <h2 data-booking-service-name class="title-orbitron mt-3 text-3xl leading-tight text-[#0b2b4c]">Selecione um serviço</h2>
                            <p data-booking-service-category class="mt-3 text-sm font-bold text-[#0b2b4c]/60"></p>

                            <div class="mt-8 space-y-4 text-sm font-bold text-slate-600">
                                <p class="flex items-center gap-3">
                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-[#0b2b4c]">◷</span>
                                    <span data-booking-service-duration>-- min</span>
                                </p>
                                <p class="flex items-center gap-3">
                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-[#0b2b4c]">R$</span>
                                    <span data-booking-service-price>--</span>
                                </p>
                            </div>

                            <div class="mt-8 space-y-4">
                                <div class="h-3 rounded-full bg-slate-100"></div>
                                <div class="h-3 w-5/6 rounded-full bg-slate-100"></div>
                                <div class="h-3 w-2/3 rounded-full bg-slate-100"></div>
                            </div>
                        </div>
                    </aside>

                    <section class="border-b border-slate-200 bg-[#fbfcff] p-6 lg:border-b-0 lg:border-r lg:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[.24em] text-[#ffcc00]">Agenda online</p>
                                <h2 class="title-orbitron mt-2 text-2xl text-[#0b2b4c]">Selecione data e horário</h2>
                            </div>
                            <button type="button" data-booking-close class="hidden h-10 w-10 cursor-pointer place-items-center rounded-full border border-slate-200 text-xl text-slate-500 transition hover:border-[#ffcc00] hover:text-black lg:grid" aria-label="Fechar">×</button>
                        </div>

                        @if ($errors->booking->any())
                            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                @foreach ($errors->booking->all() as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-8 flex items-center justify-center gap-5">
                            <button type="button" data-booking-prev-month class="grid h-11 w-11 cursor-pointer place-items-center rounded-full text-2xl text-[#0b2b4c] transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">‹</button>
                            <p data-booking-month class="min-w-44 text-center text-xl font-bold text-[#0b2b4c]">--</p>
                            <button type="button" data-booking-next-month class="grid h-11 w-11 cursor-pointer place-items-center rounded-full bg-slate-100 text-2xl text-[#0b2b4c] transition hover:bg-[#ffcc00] focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">›</button>
                        </div>

                        <div class="mt-8 grid grid-cols-7 gap-2 text-center text-xs font-black uppercase tracking-[.14em] text-slate-500">
                            <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                        </div>
                        <div data-booking-calendar class="mt-4 grid grid-cols-7 gap-2 text-center text-sm font-bold text-[#0b2b4c]"></div>

                        <div class="mt-8 border-t border-slate-200 pt-5">
                            <p class="text-sm font-black text-[#0b2b4c]">Fuso horário</p>
                            <p class="mt-2 text-sm text-slate-600">{{ config('app.timezone') }} · Horários da loja</p>
                        </div>
                    </section>

                    <aside class="bg-white p-6 lg:p-8">
                        <p data-booking-date-label class="text-xl font-bold text-[#0b2b4c]">Escolha uma data</p>
                        <div data-booking-times class="mt-7 grid gap-3"></div>

                        <p class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-sm leading-6 text-slate-600">Depois de escolher um horário, confirme seus dados para reservar a vaga.</p>
                    </aside>
                </div>

                <div data-booking-customer-panel class="absolute inset-0 z-20 hidden place-items-center bg-[#0b2b4c]/70 p-4 backdrop-blur-sm">
                    <div class="w-[min(92vw,620px)] rounded-[28px] bg-white p-5 text-[#0b2b4c] shadow-2xl sm:p-7">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[.24em] text-[#ffcc00]">Confirmar agendamento</p>
                                <h3 class="title-orbitron mt-2 text-2xl">Seus dados</h3>
                                <p class="mt-2 text-sm font-normal leading-6 text-slate-600">Informe seus dados para a loja confirmar o horário escolhido.</p>
                            </div>
                            <button type="button" data-booking-customer-close class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-full border border-slate-200 text-xl text-slate-500 transition hover:border-[#ffcc00] hover:text-black focus:outline-none focus:ring-2 focus:ring-[#ffcc00]" aria-label="Voltar para horários">×</button>
                        </div>

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                            <p><span class="text-slate-500">Serviço:</span> <span data-booking-confirm-service>--</span></p>
                            <p class="mt-1"><span class="text-slate-500">Horário:</span> <span data-booking-confirm-slot>--</span></p>
                        </div>

                        <div class="mt-5 grid gap-3">
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Nome</span>
                                    <input name="customer_name" value="{{ old('customer_name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">WhatsApp</span>
                                    <input name="customer_phone" value="{{ old('customer_phone') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Email</span>
                                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                </label>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">Placa</span>
                                        <input name="vehicle_plate" value="{{ old('vehicle_plate') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">Marca</span>
                                        <input name="vehicle_brand" value="{{ old('vehicle_brand') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">Modelo</span>
                                        <input name="vehicle_model" value="{{ old('vehicle_model') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">
                                    </label>
                                </div>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Observações</span>
                                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-[#0b2b4c] outline-none focus:border-[#ffcc00] focus:ring-2 focus:ring-[#ffcc00]/30">{{ old('notes') }}</textarea>
                                </label>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" data-booking-customer-close class="cursor-pointer rounded-xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 transition hover:border-[#ffcc00] hover:text-black focus:outline-none focus:ring-2 focus:ring-[#ffcc00]">Voltar</button>
                            <button data-booking-submit disabled class="cursor-pointer rounded-xl bg-[#ffcc00] px-5 py-3 text-sm font-black text-black transition hover:bg-black hover:text-[#ffcc00] focus:outline-none focus:ring-2 focus:ring-[#ffcc00] disabled:cursor-not-allowed disabled:opacity-40">Confirmar agendamento</button>
                        </div>
                    </div>
                </div>
            </form>
        </dialog>
    @endif

    <footer id="contato" class="bg-[#ffcc00] px-6 py-12 text-black lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-10 md:grid-cols-4">
            <div>
                @if ($tenant->logoUrl())
                    <img src="{{ $tenant->logoUrl() }}" alt="Logo da {{ $tenant->name }}" class="h-10 max-w-44 object-contain">
                @else
                    <p class="font-orbitron text-xl font-black">{{ $tenant->name }}</p>
                @endif
            </div>

            <div>
                <h3 class="font-orbitron text-sm font-semibold">Funcionamento</h3>
                <p class="mt-4 text-sm font-normal leading-6">Segunda a sábado<br>8:30 - 18h</p>
                <p class="mt-4 text-xs font-medium">Agendamento online 24/7</p>
            </div>

            <div>
                <h3 class="font-orbitron text-sm font-semibold">Fale Conosco</h3>
                <p class="mt-4 text-sm font-normal leading-6">Tel: {{ $tenant->whatsapp_phone ?: 'cadastre o WhatsApp da loja' }}</p>
                <p class="mt-4 text-xs font-medium">{{ $tenant->primary_domain ?: route('storefront', $tenant) }}</p>
            </div>

            <div>
                <h3 class="font-orbitron text-sm font-semibold">Links úteis</h3>
                <div class="mt-4 grid gap-2 text-sm font-normal">
                    <a href="#{{ $servicesAnchor }}" class="cursor-pointer hover:text-white">Serviços</a>
                    <a href="{{ route('booking', $tenant) }}" class="cursor-pointer hover:text-white">Contato</a>
                    <a href="#home" class="cursor-pointer hover:text-white">Mapa do site</a>
                </div>
            </div>
        </div>
        <p class="mx-auto mt-12 max-w-6xl text-center text-xs font-medium">Todos os direitos reservados © {{ now()->year }} {{ $tenant->name }}</p>
    </footer>

    @if ($storeWhatsappPhone !== '')
        <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3" data-whatsapp-lead>
            <div data-whatsapp-balloon hidden class="w-[min(92vw,340px)] overflow-hidden rounded-[24px] bg-white shadow-[0_24px_60px_rgba(0,0,0,.45)] ring-1 ring-black/10">
                <div class="flex items-center gap-3 bg-[#075e54] px-4 py-3 text-white">
                    <div class="grid h-10 w-10 place-items-center overflow-hidden rounded-full bg-white/15">
                        @if ($tenant->logoUrl())
                            <img src="{{ $tenant->logoUrl() }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="font-orbitron text-[10px] font-black">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($tenant->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-orbitron text-sm font-semibold">{{ $tenant->name }}</p>
                        <p class="text-[11px] font-normal text-emerald-100">online agora</p>
                    </div>
                    <button type="button" data-whatsapp-close class="grid h-8 w-8 cursor-pointer place-items-center rounded-full text-lg text-white/80 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40" aria-label="Fechar">×</button>
                </div>

                <div class="bg-[#e5ddd5] px-3 py-4" style="background-image: radial-gradient(rgba(0,0,0,.04) 1px, transparent 1px); background-size: 18px 18px;">
                    <div class="max-w-[90%] rounded-lg rounded-tl-none bg-white px-3 py-2 text-[13px] font-normal leading-5 text-[#111b21] shadow-sm">
                        Olá! 👋 Antes de abrir o WhatsApp, deixe seus dados para a {{ $tenant->name }} te atender melhor.
                        <span class="mt-1 block text-right text-[10px] font-normal text-slate-400">agora</span>
                    </div>

                    <form data-whatsapp-lead-form action="{{ $whatsappLeadAction }}" method="POST" class="mt-3 space-y-2 rounded-lg bg-white p-3 shadow-sm">
                        @csrf
                        <label class="block">
                            <span class="sr-only">Nome</span>
                            <input name="name" required maxlength="255" placeholder="Seu nome" autocomplete="name" class="w-full rounded-lg border border-slate-200 bg-[#f0f2f5] px-3 py-2.5 text-sm font-normal text-[#111b21] outline-none focus:border-[#25d366] focus:ring-2 focus:ring-[#25d366]/25">
                        </label>
                        <label class="block">
                            <span class="sr-only">Email</span>
                            <input type="email" name="email" required maxlength="255" placeholder="Seu e-mail" autocomplete="email" class="w-full rounded-lg border border-slate-200 bg-[#f0f2f5] px-3 py-2.5 text-sm font-normal text-[#111b21] outline-none focus:border-[#25d366] focus:ring-2 focus:ring-[#25d366]/25">
                        </label>
                        <label class="block">
                            <span class="sr-only">WhatsApp</span>
                            <input name="phone" required maxlength="30" placeholder="(11) 99999-9999" autocomplete="tel" inputmode="tel" data-phone-mask class="w-full rounded-lg border border-slate-200 bg-[#f0f2f5] px-3 py-2.5 text-sm font-normal text-[#111b21] outline-none focus:border-[#25d366] focus:ring-2 focus:ring-[#25d366]/25">
                        </label>
                        <p data-whatsapp-error hidden class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-600"></p>
                        <button type="submit" data-whatsapp-submit class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#25d366] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1ebe57] focus:outline-none focus:ring-2 focus:ring-[#25d366] disabled:cursor-not-allowed disabled:opacity-60">
                            Continuar no WhatsApp
                        </button>
                    </form>
                </div>
            </div>

            <button type="button" data-whatsapp-fab class="grid h-14 w-14 cursor-pointer place-items-center rounded-full bg-[#25d366] text-white shadow-[0_12px_30px_rgba(37,211,102,.45)] transition hover:scale-105 hover:bg-[#1ebe57] focus:outline-none focus:ring-2 focus:ring-[#25d366] focus:ring-offset-2 focus:ring-offset-black" aria-label="Falar no WhatsApp" aria-expanded="false">
                <svg viewBox="0 0 32 32" class="h-7 w-7" aria-hidden="true" fill="currentColor">
                    <path d="M16.02 3C9.4 3 4.02 8.36 4.02 14.96c0 2.1.55 4.14 1.6 5.95L4 29l8.3-1.57a12 12 0 0 0 3.72.58h.01c6.62 0 12-5.36 12-11.96C28.03 8.36 22.64 3 16.02 3zm6.98 17.05c-.3.84-1.73 1.55-2.42 1.65-.62.09-1.4.13-2.26-.14-.52-.16-1.19-.39-2.05-.76-3.61-1.56-5.96-5.2-6.14-5.44-.18-.24-1.47-1.95-1.47-3.72s.93-2.64 1.26-3c.33-.36.72-.45.96-.45h.7c.22 0 .52-.08.81.62.3.73 1.02 2.5 1.11 2.68.09.18.15.4.03.64-.12.24-.18.4-.36.61-.18.22-.38.48-.54.65-.18.18-.36.37-.15.73.2.36.9 1.48 1.93 2.4 1.33 1.18 2.45 1.55 2.81 1.73.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41.24-.36.48-.3.81-.18.33.12 2.1.99 2.46 1.17.36.18.6.27.69.42.09.15.09.87-.21 1.71z"/>
                </svg>
            </button>
        </div>
    @endif

    @if (! empty($bookingAvailability['services']))
        <script>
            (() => {
                const availability = @json($bookingAvailability);
                const services = availability.services || {};
                const dialog = document.querySelector('[data-booking-modal]');

                if (! dialog) {
                    return;
                }

                const calendar = dialog.querySelector('[data-booking-calendar]');
                const times = dialog.querySelector('[data-booking-times]');
                const monthLabel = dialog.querySelector('[data-booking-month]');
                const dateLabel = dialog.querySelector('[data-booking-date-label]');
                const customerPanel = dialog.querySelector('[data-booking-customer-panel]');
                const serviceInput = dialog.querySelector('[data-booking-service-input]');
                const dateInput = dialog.querySelector('[data-booking-date-input]');
                const timeInput = dialog.querySelector('[data-booking-time-input]');
                const submitButton = dialog.querySelector('[data-booking-submit]');
                const confirmService = dialog.querySelector('[data-booking-confirm-service]');
                const confirmSlot = dialog.querySelector('[data-booking-confirm-slot]');
                const monthFormatter = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
                const serviceIds = Object.keys(services);

                let selectedServiceId = dialog.dataset.oldService && services[dialog.dataset.oldService] ? dialog.dataset.oldService : serviceIds[0];
                let selectedDate = dialog.dataset.oldDate || null;
                let selectedTime = dialog.dataset.oldTime || null;
                let visibleMonth = null;

                const parseDate = (value) => {
                    const [year, month, day] = value.split('-').map(Number);

                    return new Date(year, month - 1, day);
                };

                const dateKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

                const getService = () => services[selectedServiceId];

                const getDays = () => getService()?.days || [];

                const getDay = (date) => getDays().find((day) => day.date === date);

                const setText = (selector, value) => {
                    const element = dialog.querySelector(selector);

                    if (element) {
                        element.textContent = value;
                    }
                };

                const syncHiddenInputs = () => {
                    serviceInput.value = selectedServiceId || '';
                    dateInput.value = selectedDate || '';
                    timeInput.value = selectedTime || '';
                    submitButton.disabled = ! selectedServiceId || ! selectedDate || ! selectedTime;
                };

                const closeCustomerPanel = () => {
                    customerPanel.classList.add('hidden');
                    customerPanel.classList.remove('grid');
                };

                const openCustomerPanel = () => {
                    const service = getService();
                    const day = selectedDate ? getDay(selectedDate) : null;
                    const slot = day?.times.find((time) => time.value === selectedTime);

                    if (! service || ! day || ! slot) {
                        return;
                    }

                    confirmService.textContent = service.name;
                    confirmSlot.textContent = `${day.date_label} às ${slot.label}`;
                    syncHiddenInputs();
                    customerPanel.classList.remove('hidden');
                    customerPanel.classList.add('grid');
                    customerPanel.querySelector('input[name="customer_name"]')?.focus();
                };

                const renderService = () => {
                    const service = getService();

                    if (! service) {
                        return;
                    }

                    setText('[data-booking-service-name]', service.name);
                    setText('[data-booking-service-category]', service.category || 'Serviço da loja');
                    setText('[data-booking-service-duration]', `${service.duration} min`);
                    setText('[data-booking-service-price]', service.price);

                    const days = getDays();
                    const fallbackDate = days[0]?.date || null;

                    if (! selectedDate || ! getDay(selectedDate)) {
                        selectedDate = fallbackDate;
                        selectedTime = null;
                    }

                    visibleMonth = selectedDate ? parseDate(selectedDate) : new Date();
                    visibleMonth.setDate(1);
                };

                const renderCalendar = () => {
                    calendar.replaceChildren();

                    if (! visibleMonth) {
                        visibleMonth = new Date();
                        visibleMonth.setDate(1);
                    }

                    monthLabel.textContent = monthFormatter.format(visibleMonth).replace(/^./, (letter) => letter.toUpperCase());

                    const firstDay = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
                    const lastDay = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 0);

                    for (let blank = 0; blank < firstDay.getDay(); blank++) {
                        calendar.appendChild(document.createElement('span'));
                    }

                    for (let day = 1; day <= lastDay.getDate(); day++) {
                        const date = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), day);
                        const key = dateKey(date);
                        const availableDay = getDay(key);
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = String(day);
                        button.disabled = ! availableDay;
                        button.className = availableDay
                            ? 'mx-auto grid h-12 w-12 cursor-pointer place-items-center rounded-full font-black transition focus:outline-none focus:ring-2 focus:ring-[#ffcc00]'
                            : 'mx-auto grid h-12 w-12 cursor-not-allowed place-items-center rounded-full text-slate-300';

                        if (availableDay) {
                            button.classList.add(key === selectedDate ? 'bg-[#ffcc00]' : 'bg-slate-100', key === selectedDate ? 'text-black' : 'text-[#0b2b4c]');
                            button.addEventListener('click', () => {
                                selectedDate = key;
                                selectedTime = null;
                                renderCalendar();
                                renderTimes();
                            });
                        }

                        calendar.appendChild(button);
                    }
                };

                const renderTimes = () => {
                    times.replaceChildren();
                    const day = selectedDate ? getDay(selectedDate) : null;
                    dateLabel.textContent = day?.date_label || 'Escolha uma data';

                    if (! day) {
                        const empty = document.createElement('p');
                        empty.className = 'rounded-xl border border-slate-200 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500';
                        empty.textContent = 'Nenhum horário disponível para este mês.';
                        times.appendChild(empty);
                        syncHiddenInputs();

                        return;
                    }

                    day.times.forEach((slot) => {
                        const row = document.createElement('div');
                        row.className = 'grid gap-2 sm:grid-cols-[1fr_auto]';

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = slot.label;
                        button.className = slot.value === selectedTime
                            ? 'cursor-pointer rounded-xl bg-[#0b2b4c] px-5 py-4 text-sm font-black text-white transition focus:outline-none focus:ring-2 focus:ring-[#ffcc00]'
                            : 'cursor-pointer rounded-xl border-2 border-[#ffcc00] bg-white px-5 py-4 text-sm font-black text-[#0b2b4c] transition hover:bg-[#ffcc00] focus:outline-none focus:ring-2 focus:ring-[#ffcc00]';
                        button.addEventListener('click', () => {
                            selectedTime = slot.value;
                            renderTimes();
                        });

                        row.appendChild(button);

                        if (slot.value === selectedTime) {
                            const confirm = document.createElement('button');
                            confirm.type = 'button';
                            confirm.textContent = 'Confirmar';
                            confirm.className = 'cursor-pointer rounded-xl bg-[#ffcc00] px-5 py-4 text-sm font-black text-black transition hover:bg-black hover:text-[#ffcc00] focus:outline-none focus:ring-2 focus:ring-[#ffcc00]';
                            confirm.addEventListener('click', openCustomerPanel);
                            row.appendChild(confirm);
                        }

                        times.appendChild(row);
                    });

                    syncHiddenInputs();
                };

                const openBooking = (serviceId) => {
                    selectedServiceId = services[serviceId] ? serviceId : serviceIds[0];
                    selectedDate = null;
                    selectedTime = null;
                    closeCustomerPanel();
                    renderService();
                    renderCalendar();
                    renderTimes();
                    dialog.showModal();
                };

                document.querySelectorAll('[data-booking-open]').forEach((button) => {
                    button.addEventListener('click', () => openBooking(button.dataset.bookingOpen));
                });

                dialog.querySelectorAll('[data-booking-close]').forEach((button) => {
                    button.addEventListener('click', () => {
                        closeCustomerPanel();
                        dialog.close();
                    });
                });

                dialog.querySelectorAll('[data-booking-customer-close]').forEach((button) => {
                    button.addEventListener('click', closeCustomerPanel);
                });

                dialog.querySelector('[data-booking-prev-month]')?.addEventListener('click', () => {
                    visibleMonth.setMonth(visibleMonth.getMonth() - 1);
                    renderCalendar();
                    renderTimes();
                });

                dialog.querySelector('[data-booking-next-month]')?.addEventListener('click', () => {
                    visibleMonth.setMonth(visibleMonth.getMonth() + 1);
                    renderCalendar();
                    renderTimes();
                });

                renderService();
                renderCalendar();
                renderTimes();

                if (dialog.dataset.openOnError === '1') {
                    dialog.showModal();

                    if (selectedTime) {
                        openCustomerPanel();
                    }
                }
            })();
        </script>
    @endif

    @if ($storeWhatsappPhone !== '')
        <script>
            (() => {
                const root = document.querySelector('[data-whatsapp-lead]');
                const fab = root?.querySelector('[data-whatsapp-fab]');
                const balloon = root?.querySelector('[data-whatsapp-balloon]');
                const closeButton = root?.querySelector('[data-whatsapp-close]');
                const form = root?.querySelector('[data-whatsapp-lead-form]');
                const errorBox = root?.querySelector('[data-whatsapp-error]');
                const submitButton = root?.querySelector('[data-whatsapp-submit]');

                if (! root || ! fab || ! balloon || ! form) {
                    return;
                }

                const setOpen = (open) => {
                    balloon.hidden = ! open;
                    fab.setAttribute('aria-expanded', open ? 'true' : 'false');

                    if (open) {
                        form.querySelector('input[name="name"]')?.focus();
                    }
                };

                fab.addEventListener('click', () => setOpen(balloon.hidden));
                closeButton?.addEventListener('click', () => setOpen(false));

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && ! balloon.hidden) {
                        setOpen(false);
                    }
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    errorBox.hidden = true;
                    errorBox.textContent = '';
                    submitButton.disabled = true;
                    submitButton.textContent = 'Abrindo WhatsApp...';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (! response.ok) {
                            const firstError = payload.errors
                                ? Object.values(payload.errors).flat()[0]
                                : payload.message;

                            throw new Error(firstError || 'Não foi possível salvar seus dados.');
                        }

                        window.location.href = payload.whatsapp_url;
                    } catch (error) {
                        errorBox.textContent = error.message || 'Não foi possível salvar seus dados.';
                        errorBox.hidden = false;
                        submitButton.disabled = false;
                        submitButton.textContent = 'Continuar no WhatsApp';
                    }
                });
            })();
        </script>
    @endif

    {!! $landingPage?->custom_javascript !!}
</body>
</html>
