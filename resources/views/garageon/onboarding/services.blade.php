@extends('garageon.onboarding.layout')

@section('title', 'Serviços')

@section('content')
    <div class="border-b border-white/10 pb-5">
        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Etapa 2 de 5</p>
        <h2 class="mt-2 text-2xl font-black">Cadastre seus serviços</h2>
        <p class="mt-2 text-sm text-zinc-400">Comece com pelo menos um serviço. Você pode complementar depois em Configurações.</p>
    </div>

    @if ($services->isNotEmpty())
        <ul class="mt-6 space-y-2">
            @foreach ($services as $service)
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/35 px-4 py-3">
                    <div>
                        <p class="font-bold text-white">{{ $service->name }}</p>
                        <p class="text-xs text-zinc-400">{{ $service->category }} · {{ $service->duration_minutes }} min</p>
                    </div>
                    <p class="font-orbitron text-sm font-black text-yellow-300">R$ {{ number_format((float) $service->price, 2, ',', '.') }}</p>
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('onboarding.services.store') }}" class="mt-6 space-y-4">
        @csrf

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Nome do serviço</span>
            <input name="name" value="{{ old('name') }}" required placeholder="Ex: Lavagem técnica" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        </label>

        <div class="grid gap-4 sm:grid-cols-3">
            <label class="block">
                <span class="text-sm font-bold text-zinc-200">Categoria</span>
                <select name="category" required class="mt-2 w-full cursor-pointer rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                    <option value="">Escolha</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->name }}" @selected(old('category') === $category->name)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-bold text-zinc-200">Duração (min)</span>
                <input type="number" name="duration_minutes" min="15" step="15" value="{{ old('duration_minutes', 60) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            </label>

            <label class="block">
                <span class="text-sm font-bold text-zinc-200">Preço</span>
                <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}" required placeholder="0,00" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            </label>
        </div>

        <div class="flex flex-wrap justify-end gap-3 border-t border-white/10 pt-5">
            <button type="submit" name="continue" value="0" class="cursor-pointer rounded-2xl border border-white/10 bg-white/[.04] px-5 py-3 text-sm font-bold text-zinc-200 transition hover:bg-white/[.08]">Salvar e adicionar outro</button>
            <button type="submit" name="continue" value="1" class="cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar e continuar</button>
        </div>
    </form>

    @if ($services->isNotEmpty())
        <form method="POST" action="{{ route('onboarding.skip-step', ['step' => 'services']) }}" class="mt-4 flex justify-end">
            @csrf
            <button type="submit" class="cursor-pointer text-sm font-bold text-yellow-200 underline-offset-4 hover:underline">Já cadastrei, continuar sem adicionar</button>
        </form>
    @endif
@endsection
