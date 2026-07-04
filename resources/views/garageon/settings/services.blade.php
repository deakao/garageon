<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-7xl">
            @include('garageon.dashboard.header')

            @if (session('status'))
                <p class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            @if ($errors->any() && ! $errors->getBag('categories')->any())
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
                            <x-tabler-list-check class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Total de serviços</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($serviceStats['total'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">catálogo da loja</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-circle-check class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Ativos no catálogo</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($serviceStats['active'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">visíveis na agenda e na loja</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-category class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Categorias</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($serviceStats['categories'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">organização do portfólio</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="font-orbitron text-2xl font-black text-white">Serviços</h1>
                        <p class="mt-1 text-sm text-zinc-400">Gerencie o catálogo usado na agenda, orçamentos e landing page.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <button type="button" data-modal-open="category-modal" class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-yellow-300/30 px-5 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Categorias
                        </button>
                        <button type="button" data-modal-open="service-create-modal" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Novo serviço
                        </button>
                    </div>
                </div>

                <div class="services-datatable">
                    <table data-services-table>
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Categoria</th>
                                <th>Duração</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $service)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <div class="h-12 w-16 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-white/[.04]">
                                                    @if ($service->thumbnailUrl())
                                                        <img src="{{ $service->thumbnailUrl() }}" alt="Thumbnail do serviço {{ $service->name }}" class="h-full w-full object-cover">
                                                    @else
                                                        <div class="h-full w-full bg-[linear-gradient(135deg,rgba(250,204,21,.22),transparent_45%),#050505]"></div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong class="block text-sm font-black text-white">{{ $service->name }}</strong>                                       </div>
                                            </div>
                                            @if ($service->description)
                                                <span class="sr-only">{{ $service->description }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $service->category }}</td>
                                    <td>{{ $service->duration_minutes }} min</td>
                                    <td>R$ {{ number_format((float) $service->price, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $service->is_active ? 'bg-yellow-300 text-black' : 'bg-white/10 text-zinc-400' }}">{{ $service->is_active ? 'Ativo' : 'Inativo' }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" data-modal-open="service-edit-{{ $service->id }}" class="rounded-xl border border-yellow-300/30 px-3 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                                Editar
                                            </button>
                                            @if ($service->is_active)
                                                <form method="POST" action="{{ route('settings.services.destroy', $service) }}" onsubmit="return confirm('Desativar este serviço? Ele sai do catálogo, mas o histórico continua preservado.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-xl border border-red-300/30 px-3 py-2 text-xs font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                        Desativar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <dialog id="service-create-modal" class="customer-modal w-[min(94vw,980px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                <form method="POST" action="{{ route('settings.services.store') }}" enctype="multipart/form-data" class="p-6 sm:p-8">
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Novo serviço</p>
                            <h2 class="mt-2 text-2xl font-black">Produto vendável</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-400">Serviços ativos aparecem no agendamento e no modal da agenda.</p>
                        </div>
                        <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                    </div>

                    <div class="mt-6 grid gap-4">
                        @include('garageon.settings.service-fields', ['service' => null])
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-modal-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                        <button @disabled($categories->isEmpty()) class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">Criar serviço</button>
                    </div>
                </form>
            </dialog>

            @foreach ($services as $service)
                <dialog id="service-edit-{{ $service->id }}" class="customer-modal w-[min(94vw,980px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                    <form method="POST" action="{{ route('settings.services.update', $service) }}" enctype="multipart/form-data" class="p-6 sm:p-8">
                        @csrf
                        @method('PUT')
                        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Editar serviço</p>
                                <h2 class="mt-2 text-2xl font-black">{{ $service->name }}</h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-400">Atualize preço, duração e visibilidade no catálogo.</p>
                            </div>
                            <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                        </div>

                        <div class="mt-6 grid gap-4">
                            @include('garageon.settings.service-fields', ['service' => $service])
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button type="button" data-modal-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                            <button @disabled($categories->isEmpty()) class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">Salvar alterações</button>
                        </div>
                    </form>
                </dialog>
            @endforeach

            <dialog id="category-modal" class="customer-modal w-[min(94vw,760px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm" @if ($errors->getBag('categories')->any()) open @endif>
                <div class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Categorias</p>
                            <h2 class="mt-2 text-2xl font-black">Organize o catálogo</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-400">Crie categorias para padronizar os serviços que aparecem no dashboard, agenda e loja.</p>
                        </div>
                        <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                    </div>

                    @if ($errors->getBag('categories')->any())
                        <div class="mt-5 rounded-2xl border border-red-300/25 bg-red-300/10 px-4 py-3 text-sm text-red-100">
                            @foreach ($errors->getBag('categories')->all() as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.service-categories.store') }}" class="mt-6 grid gap-3 rounded-2xl border border-white/10 bg-black/35 p-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Nova categoria</span>
                            <input name="name" value="{{ old('name') }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Lavagem técnica">
                        </label>

                        <button class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Criar
                        </button>
                    </form>

                    <div class="mt-6 space-y-3">
                        @forelse ($categories as $category)
                            @php($usageCount = $categoryUsage->get($category->name, 0))
                            <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/[.04] p-4 lg:grid-cols-[1fr_auto] lg:items-center">
                                <form id="category-update-{{ $category->id }}" method="POST" action="{{ route('settings.service-categories.update', $category) }}" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                    @csrf
                                    @method('PUT')
                                    <label class="block">
                                        <span class="text-xs font-black uppercase tracking-[.18em] text-zinc-500">{{ $usageCount }} {{ $usageCount === 1 ? 'serviço' : 'serviços' }}</span>
                                        <input name="name" value="{{ $category->name }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    </label>

                                    <button class="rounded-2xl border border-yellow-300/30 px-4 py-3 text-sm font-bold text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                        Salvar
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('settings.service-categories.destroy', $category) }}" onsubmit="return confirm('Excluir esta categoria?');">
                                    @csrf
                                    @method('DELETE')
                                    <button @disabled($usageCount > 0) class="w-full rounded-2xl border border-red-300/30 px-4 py-3 text-sm font-bold text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300 disabled:cursor-not-allowed disabled:opacity-40 lg:w-auto">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/15 p-8 text-center text-zinc-400">
                                Nenhuma categoria cadastrada. Crie a primeira para liberar o cadastro de serviços.
                            </div>
                        @endforelse
                    </div>
                </div>
            </dialog>
        </div>
    </main>
</body>
</html>
