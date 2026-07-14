<dialog data-quick-service-modal class="customer-modal w-[min(94vw,560px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
    <form method="POST" action="{{ route('services.quick-store') }}" data-quick-service-form class="p-6 sm:p-8">
        @csrf

        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
            <div>
                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Cadastro rápido</p>
                <h2 class="mt-2 font-orbitron text-2xl font-black">Novo serviço</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-400">Crie sem sair deste fluxo. A duração inicial será de 60 minutos.</p>
            </div>
            <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
        </div>

        <div data-quick-service-errors class="mt-5 hidden rounded-2xl border border-red-300/25 bg-red-300/10 px-4 py-3 text-sm text-red-100" role="alert" aria-live="polite"></div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="text-sm font-bold text-zinc-200">Nome</span>
                <input name="name" required maxlength="255" autocomplete="off" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Lavagem Premium">
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-bold text-zinc-200">Valor</span>
                <input type="number" name="price" required min="0" max="999999.99" step="0.01" inputmode="decimal" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="149,90">
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-bold text-zinc-200">Descrição</span>
                <textarea name="description" rows="3" maxlength="1000" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Descreva o que está incluído."></textarea>
            </label>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
            <button type="button" data-modal-close class="cursor-pointer rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
            <button type="submit" data-quick-service-submit class="cursor-pointer rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">Cadastrar serviço</button>
        </div>
    </form>
</dialog>
