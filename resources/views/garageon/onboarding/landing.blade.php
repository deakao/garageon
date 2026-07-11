@extends('garageon.onboarding.layout')

@section('title', 'Landing page')

@section('content')
    @php
        $headline = old('headline', $landingPage?->headline ?? 'Bem vindos à '.$tenant->name);
        $subheadline = old('subheadline', $landingPage?->subheadline ?? 'Cada veículo recebe tratamento individualizado, deixando seu veículo novo de novo.');
        $ctaLabel = old('cta_label', $landingPage?->cta_label ?? 'Orçamento Grátis');
        $published = old('published', $landingPage?->published_at ? '1' : '0');
    @endphp

    <div class="border-b border-white/10 pb-5">
        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Etapa 5 de 5</p>
        <h2 class="mt-2 text-2xl font-black">Landing page da loja</h2>
        <p class="mt-2 text-sm text-zinc-400">Defina a promessa principal. SEO, pixels e depoimentos você ajusta depois.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.landing.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Headline</span>
            <input name="headline" value="{{ $headline }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Subheadline</span>
            <textarea name="subheadline" rows="3" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ $subheadline }}</textarea>
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Texto do botão</span>
            <input name="cta_label" value="{{ $ctaLabel }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        </label>

        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
            <input type="checkbox" name="published" value="1" @checked($published) class="h-4 w-4 cursor-pointer rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
            Publicar agora em /loja/{{ $tenant->slug }}
        </label>

        <div class="flex justify-end border-t border-white/10 pt-5">
            <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Concluir e ir ao dashboard</button>
        </div>
    </form>
@endsection
