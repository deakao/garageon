@php
    $formatPhone = function (?string $phone): string {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 13 && str_starts_with($digits, '55')) {
            return '+55 ('.substr($digits, 2, 2).') '.substr($digits, 4, 5).'-'.substr($digits, 9);
        }

        if (strlen($digits) === 11) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        }

        return $phone ?: 'WhatsApp não informado';
    };
@endphp

@forelse ($conversations as $conversation)
    @php
        $isActive = $selectedConversation?->id === $conversation->id;
        $name = $conversation->contact_name ?: $conversation->customer?->name ?: $formatPhone($conversation->contact_phone);
        $vehicle = $conversation->customer?->vehicles?->first();
    @endphp
    <a href="{{ route('chat.index', ['conversation' => $conversation->id]) }}" data-chat-item data-chat-search-text="{{ \Illuminate\Support\Str::lower($name.' '.$conversation->contact_phone.' '.$conversation->last_message) }}" class="group mb-1 flex gap-3 rounded-2xl border px-3 py-3 transition focus:outline-none focus:ring-2 focus:ring-yellow-300 {{ $isActive ? 'border-yellow-300/30 bg-yellow-300/10' : 'border-transparent hover:border-white/10 hover:bg-white/[.04]' }}">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-zinc-700 to-zinc-900 text-sm font-black text-white ring-1 ring-white/10">{{ collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') }}</div>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <strong class="block truncate text-sm font-black text-white">{{ $name }}</strong>
                <span class="shrink-0 text-[10px] font-bold text-zinc-500">{{ $conversation->last_message_at?->format('H:i') }}</span>
            </div>
            <p class="mt-1 truncate text-xs text-zinc-400">{{ $conversation->last_message ?: 'Conversa pronta para atendimento.' }}</p>
            <div class="mt-2 flex items-center justify-between gap-2">
                <span class="truncate text-[10px] font-bold uppercase tracking-[.12em] text-zinc-600">{{ $vehicle ? trim($vehicle->brand.' '.$vehicle->model) : $formatPhone($conversation->contact_phone) }}</span>
                @if ($conversation->unread_count > 0 && ! $isActive)
                    <span class="grid h-5 min-w-5 place-items-center rounded-full bg-yellow-300 px-1.5 text-[10px] font-black text-black">{{ $conversation->unread_count }}</span>
                @endif
            </div>
        </div>
    </a>
@empty
    <div class="m-2 rounded-2xl border border-white/10 bg-black/30 p-4 text-sm leading-6 text-zinc-400">Nenhuma conversa recebida ainda. Escolha um cliente abaixo para iniciar o atendimento.</div>
@endforelse

@if ($customersWithoutConversation->isNotEmpty())
    <p class="px-3 pb-2 pt-4 font-orbitron text-[10px] font-black uppercase tracking-[.22em] text-zinc-600">Clientes sem conversa</p>
    @foreach ($customersWithoutConversation as $customer)
        @php
            $isActiveCustomer = $selectedCustomer?->id === $customer->id;
            $vehicle = $customer->vehicles->first();
        @endphp
        <a href="{{ route('chat.index', ['customer' => $customer->id]) }}" data-chat-item data-chat-search-text="{{ \Illuminate\Support\Str::lower($customer->name.' '.$customer->phone) }}" class="group mb-1 flex gap-3 rounded-2xl border px-3 py-3 transition focus:outline-none focus:ring-2 focus:ring-yellow-300 {{ $isActiveCustomer ? 'border-yellow-300/30 bg-yellow-300/10' : 'border-transparent hover:border-white/10 hover:bg-white/[.04]' }}">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-black text-sm font-black text-zinc-300 ring-1 ring-white/10">{{ collect(explode(' ', trim($customer->name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') }}</div>
            <div class="min-w-0 flex-1">
                <strong class="block truncate text-sm font-black text-white">{{ $customer->name }}</strong>
                <p class="mt-1 truncate text-xs text-zinc-400">{{ $formatPhone($customer->phone) }}</p>
                <span class="mt-2 block truncate text-[10px] font-bold uppercase tracking-[.12em] text-zinc-600">{{ $vehicle ? trim($vehicle->brand.' '.$vehicle->model) : 'Começar conversa' }}</span>
            </div>
        </a>
    @endforeach
@endif
