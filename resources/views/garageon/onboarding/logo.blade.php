@extends('garageon.onboarding.layout')

@section('title', 'Logo')

@section('content')
    <div class="border-b border-white/10 pb-5">
        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Etapa 3 de 5</p>
        <h2 class="mt-2 text-2xl font-black">Logo da empresa</h2>
        <p class="mt-2 text-sm text-zinc-400">Aparece no painel e na landing page da loja. JPG, PNG ou WebP até 2 MB.</p>
    </div>

    @if ($tenant->logoUrl())
        <div class="mt-6 flex items-center gap-4 rounded-2xl border border-white/10 bg-black/35 p-4">
            <img src="{{ $tenant->logoUrl() }}" alt="Logo de {{ $tenant->name }}" class="h-16 w-16 rounded-xl object-contain bg-white/5 p-1">
            <p class="text-sm text-zinc-300">Logo atual. Envie outra para substituir ou continue.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('onboarding.logo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Arquivo da logo</span>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="mt-2 w-full cursor-pointer rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-zinc-300 file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-yellow-300 file:px-4 file:py-2 file:text-sm file:font-black file:text-black hover:file:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300/30">
        </label>

        <div class="flex justify-end border-t border-white/10 pt-5">
            <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Continuar</button>
        </div>
    </form>

    @if ($tenant->logoUrl())
        <form method="POST" action="{{ route('onboarding.skip-step', ['step' => 'logo']) }}" class="mt-4 flex justify-end">
            @csrf
            <button type="submit" class="cursor-pointer text-sm font-bold text-yellow-200 underline-offset-4 hover:underline">Manter logo atual e continuar</button>
        </form>
    @endif
@endsection
