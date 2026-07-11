@extends('garageon.onboarding.layout')

@section('title', 'Atendente virtual')

@section('content')
    <div class="border-b border-white/10 pb-5">
        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Etapa 4 de 5</p>
        <h2 class="mt-2 text-2xl font-black">Atendente virtual</h2>
        <p class="mt-2 text-sm text-zinc-400">Defina o nome e o tom. A conexão do WhatsApp e a chave de IA ficam em Configurações.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.attendant.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Nome do atendente</span>
            <input name="name" value="{{ old('name', $attendant->name ?: 'Piloto Automático') }}" required maxlength="80" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Tom de voz</span>
            <select name="tone" required class="mt-2 w-full cursor-pointer rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                @foreach ($toneOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('tone', $attendant->tone?->value ?? 'friendly') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Contexto da loja</span>
            <textarea name="context" rows="4" maxlength="5000" placeholder="Ex: Especialistas em vitrificação e PPF na zona sul. Atendemos sob agendamento." class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ old('context', $attendant->context) }}</textarea>
        </label>

        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $attendant->is_active)) class="h-4 w-4 cursor-pointer rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
            Tentar ligar agora (só ativa se já houver chave de IA disponível)
        </label>

        <div class="flex justify-end border-t border-white/10 pt-5">
            <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Continuar</button>
        </div>
    </form>
@endsection
