<div class="grid gap-4 rounded-2xl border border-white/10 bg-black/35 p-4 md:grid-cols-[180px_1fr] md:items-center">
    <div class="h-32 overflow-hidden rounded-2xl border border-yellow-300/20 bg-white/[.04]">
        @if ($service?->thumbnailUrl())
            <img src="{{ $service->thumbnailUrl() }}" alt="Thumbnail do serviço {{ $service->name }}" class="h-full w-full object-cover">
        @else
            <div class="grid h-full place-items-center bg-[linear-gradient(135deg,rgba(250,204,21,.22),transparent_45%),#050505] p-4 text-center">
                <span class="font-orbitron text-xs font-black uppercase tracking-[.22em] text-yellow-300">Sem thumbnail</span>
            </div>
        @endif
    </div>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Thumbnail do serviço</span>
        <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/webp" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-zinc-300 file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-yellow-300 file:px-4 file:py-2 file:text-sm file:font-black file:text-black hover:file:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300/30">
        <span class="mt-2 block text-xs leading-5 text-zinc-500">Envie JPG, PNG ou WebP até 2 MB. Essa imagem aparece na landing page da loja.</span>
        @error('thumbnail') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>
</div>

<label class="block">
    <span class="text-sm font-bold text-zinc-200">Nome</span>
    <input name="name" value="{{ old('name', $service?->name) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
    @error('name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
</label>

<label class="block">
    <span class="text-sm font-bold text-zinc-200">Descrição</span>
    <textarea name="description" rows="3" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">{{ old('description', $service?->description) }}</textarea>
    @error('description') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
</label>

<div class="grid gap-4 sm:grid-cols-3">
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Duração</span>
        <input type="number" name="duration_minutes" min="15" step="15" value="{{ old('duration_minutes', $service?->duration_minutes ?? 60) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('duration_minutes') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Preço</span>
        <input type="number" name="price" min="0" step="0.01" value="{{ old('price', $service?->price ?? 0) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('price') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Retorno em dias</span>
        <input type="number" name="lifecycle_days" min="1" value="{{ old('lifecycle_days', $service?->lifecycle_days) }}" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
        @error('lifecycle_days') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
    </label>
</div>

<div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
    <label class="block">
        <span class="text-sm font-bold text-zinc-200">Categoria</span>
        <select name="category" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
            <option value="">Escolha uma categoria</option>
            @foreach ($categories as $category)
                <option value="{{ $category->name }}" @selected(old('category', $service?->category) === $category->name)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
        @if ($categories->isEmpty())
            <span class="mt-2 block text-xs text-yellow-100">Crie uma categoria antes de salvar serviços.</span>
        @endif
    </label>

    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm font-bold text-zinc-200">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service?->is_active ?? true)) class="h-4 w-4 rounded border-white/20 bg-black text-yellow-300 focus:ring-yellow-300">
        Ativo
    </label>
</div>
