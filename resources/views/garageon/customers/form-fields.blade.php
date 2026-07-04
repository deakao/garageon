<div class="grid gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Nome do cliente</span>
        <input name="name" value="{{ old('name', $customer?->name) }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Jane Cooper">
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">WhatsApp</span>
        <input name="phone" value="{{ old('phone', $customer?->phone) }}" required maxlength="30" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="(11) 99999-9999">
    </label>
</div>

<label class="block">
    <span class="text-sm font-bold text-zinc-200">E-mail</span>
    <input type="email" name="email" value="{{ old('email', $customer?->email) }}" maxlength="255" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="cliente@email.com">
</label>

<label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[.04] px-4 py-3 text-sm font-bold text-zinc-200">
    <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $customer?->marketing_consent ?? true)) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
    Aceita receber lembretes e campanhas
</label>
