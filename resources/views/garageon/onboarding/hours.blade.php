@extends('garageon.onboarding.layout')

@section('title', 'Horários')

@section('content')
    <div class="border-b border-white/10 pb-5">
        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Etapa 1 de 5</p>
        <h2 class="mt-2 text-2xl font-black">Horários de funcionamento</h2>
        <p class="mt-2 text-sm text-zinc-400">Defina quando a agenda pode oferecer vagas para clientes.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.hours.update') }}" class="mt-6 space-y-3">
        @csrf
        @method('PUT')

        @foreach ([0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'] as $day => $label)
            @php
                $hour = $hours->get($day);
                $isClosed = old("hours.$day.is_closed", $hour?->is_closed ?? $day === 0);
            @endphp
            <div class="grid gap-4 rounded-2xl border border-white/10 bg-black/35 p-4 md:grid-cols-[1fr_150px_150px_auto] md:items-center">
                <strong class="font-orbitron text-sm text-white">{{ $label }}</strong>

                <label class="block">
                    <span class="text-xs font-bold text-zinc-400">Abre</span>
                    <input type="time" name="hours[{{ $day }}][opens_at]" value="{{ old("hours.$day.opens_at", $hour?->opens_at ? substr($hour->opens_at, 0, 5) : '08:00') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                </label>

                <label class="block">
                    <span class="text-xs font-bold text-zinc-400">Fecha</span>
                    <input type="time" name="hours[{{ $day }}][closes_at]" value="{{ old("hours.$day.closes_at", $hour?->closes_at ? substr($hour->closes_at, 0, 5) : '18:00') }}" class="mt-1 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                </label>

                <label class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[.04] px-3 py-2 text-sm font-bold text-zinc-200">
                    <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" @checked($isClosed) class="h-4 w-4 cursor-pointer rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
                    Fechado
                </label>
            </div>
        @endforeach

        <div class="flex justify-end border-t border-white/10 pt-5">
            <button type="submit" class="cursor-pointer rounded-2xl bg-yellow-300 px-6 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Continuar</button>
        </div>
    </form>
@endsection
