@php
    $automation ??= new \App\Models\QuoteFunnelAutomation([
        'is_active' => true,
        'delay_value' => 0,
        'delay_unit' => 'hours',
        'channel' => 'whatsapp',
        'stage' => 'pending',
    ]);
    $stages = \App\Models\QuoteFunnelAutomation::STAGES;
    $channels = \App\Models\QuoteFunnelAutomation::CHANNELS;
    $delayUnits = \App\Models\QuoteFunnelAutomation::DELAY_UNITS;
@endphp

<div class="grid gap-5">
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Nome da automação</span>
        <input name="name" value="{{ old('name', $automation->name) }}" required maxlength="120" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex.: Follow-up WhatsApp em 1 dia">
        @error('name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Etapa do funil</span>
            <select name="stage" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                @foreach ($stages as $value => $label)
                    <option value="{{ $value }}" @selected(old('stage', $automation->stage) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('stage') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Canal</span>
            <select name="channel" required data-automation-channel class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                @foreach ($channels as $value => $label)
                    <option value="{{ $value }}" @selected(old('channel', $automation->channel) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('channel') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-[1fr_160px]">
        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Aguardar</span>
            <input type="number" name="delay_value" min="0" max="365" value="{{ old('delay_value', $automation->delay_value ?? 0) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            @error('delay_value') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-bold text-zinc-200">Unidade</span>
            <select name="delay_unit" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                @foreach ($delayUnits as $value => $label)
                    <option value="{{ $value }}" @selected(old('delay_unit', $automation->delay_unit ?? 'hours') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('delay_unit') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        </label>
    </div>
    <p class="text-xs text-zinc-500">Use 0 para enviar imediatamente ao entrar na etapa. Ex.: 2 horas, 1 dia, 30 minutos.</p>

    <label class="block" data-automation-subject-field>
        <span class="text-sm font-bold text-zinc-200">Assunto do e-mail</span>
        <input name="subject" value="{{ old('subject', $automation->subject) }}" maxlength="180" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Orçamento @{{orcamento}} - @{{loja}}">
        @error('subject') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Mensagem</span>
        <textarea name="message_template" rows="7" required maxlength="4000" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Olá @{{cliente}}...">{{ old('message_template', $automation->message_template) }}</textarea>
        @error('message_template') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="inline-flex cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-black/25 px-4 py-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $automation->is_active ?? true)) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
        <span class="text-sm font-bold text-zinc-200">Automação ativa</span>
    </label>
</div>
