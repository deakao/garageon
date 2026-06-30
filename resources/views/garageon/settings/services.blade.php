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
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>

        <div class="relative mx-auto max-w-6xl">
            @include('garageon.settings.nav')

            @if (session('status'))
                <p class="mt-6 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            <section class="mt-6 grid gap-6 xl:grid-cols-[.9fr_1.2fr]">
                <article class="rounded-[28px] border border-yellow-300/20 bg-[#101010] p-6 shadow-2xl shadow-black/30">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Novo serviço</p>
                            <h2 class="mt-2 text-2xl font-black">Produto vendável</h2>
                            <p class="mt-2 text-sm text-zinc-400">Serviços ativos aparecem no agendamento e no modal da agenda.</p>
                        </div>

                        <button type="button" data-category-modal-open class="rounded-2xl border border-yellow-300/30 px-4 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                            Categorias
                        </button>
                    </div>

                    <form method="POST" action="{{ route('settings.services.store') }}" class="mt-6 grid gap-4">
                        @csrf
                        @include('garageon.settings.service-fields', ['service' => null, 'button' => 'Criar serviço'])
                    </form>
                </article>

                <article class="rounded-[28px] border border-white/10 bg-white/[.05] p-6 shadow-2xl shadow-black/30">
                    <div class="flex flex-col gap-2 border-b border-white/10 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Catálogo</p>
                            <h2 class="mt-2 text-2xl font-black">{{ $services->count() }} serviços</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($services as $service)
                            <details class="rounded-2xl border border-white/10 bg-black/35 p-4" {{ $loop->first ? 'open' : '' }}>
                                <summary class="cursor-pointer list-none">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <strong class="font-orbitron text-base text-white">{{ $service->name }}</strong>
                                            <span class="mt-1 block text-sm text-zinc-400">{{ $service->duration_minutes }} min · R$ {{ number_format((float) $service->price, 2, ',', '.') }} · {{ $service->category }}</span>
                                        </div>
                                        <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $service->is_active ? 'bg-yellow-300 text-black' : 'bg-white/10 text-zinc-400' }}">{{ $service->is_active ? 'Ativo' : 'Inativo' }}</span>
                                    </div>
                                </summary>

                                <form method="POST" action="{{ route('settings.services.update', $service) }}" class="mt-5 grid gap-4 border-t border-white/10 pt-5">
                                    @csrf
                                    @method('PUT')
                                    @include('garageon.settings.service-fields', ['service' => $service, 'button' => 'Salvar alterações'])
                                </form>

                                <form method="POST" action="{{ route('settings.services.destroy', $service) }}" class="mt-3" onsubmit="return confirm('Desativar este serviço? Ele sai do catálogo, mas o histórico continua preservado.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-2xl border border-red-300/30 px-4 py-2 text-sm font-bold text-red-200 transition hover:bg-red-300/10">Desativar serviço</button>
                                </form>
                            </details>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/15 p-8 text-center text-zinc-400">Nenhum serviço cadastrado ainda. Crie o primeiro para liberar a agenda.</div>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </main>

    <div data-category-modal class="fixed inset-0 z-50 {{ $errors->getBag('categories')->any() ? 'flex' : 'hidden' }} items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
        <button type="button" data-category-modal-close class="absolute inset-0 bg-black/80 backdrop-blur-sm" aria-label="Fechar categorias"></button>

        <section class="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[28px] border border-yellow-300/25 bg-[#101010] p-6 text-white shadow-2xl shadow-black/70 sm:p-8">
            <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Categorias</p>
                    <h2 id="category-modal-title" class="mt-2 text-2xl font-black">Organize o catálogo</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">Crie categorias para padronizar os serviços que aparecem no dashboard, agenda e loja.</p>
                </div>

                <button type="button" data-category-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
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
        </section>
    </div>

    <script>
        const categoryModal = document.querySelector('[data-category-modal]');

        const openCategoryModal = () => {
            categoryModal?.classList.remove('hidden');
            categoryModal?.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        };

        const closeCategoryModal = () => {
            categoryModal?.classList.add('hidden');
            categoryModal?.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        document.querySelectorAll('[data-category-modal-open]').forEach((button) => button.addEventListener('click', openCategoryModal));
        document.querySelectorAll('[data-category-modal-close]').forEach((button) => button.addEventListener('click', closeCategoryModal));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCategoryModal();
            }
        });
    </script>
</body>
</html>
