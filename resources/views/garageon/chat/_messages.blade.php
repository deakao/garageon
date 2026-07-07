@if ($messages->isEmpty())
    <div class="mx-auto mt-16 max-w-md rounded-3xl border border-yellow-300/20 bg-yellow-300/[.06] p-6 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-yellow-300 text-black">
            <x-tabler-brand-whatsapp class="h-7 w-7" stroke-width="2.4" />
        </div>
        <h3 class="mt-4 font-orbitron text-xl font-black">Pronto para conversar</h3>
        <p class="mt-2 text-sm leading-6 text-zinc-400">Envie a primeira mensagem para abrir o atendimento desse cliente no WhatsApp.</p>
    </div>
@else
    @php
        $currentDate = null;
    @endphp
    @foreach ($messages as $message)
        @php
            $messageDate = ($message->occurred_at ?: $message->created_at)->format('d/m/Y');
            $isOutbound = $message->direction === 'outbound';
        @endphp

        @if ($currentDate !== $messageDate)
            @php($currentDate = $messageDate)
            <div class="my-4 flex justify-center">
                <span class="rounded-full border border-white/10 bg-black/45 px-3 py-1 text-[10px] font-black uppercase tracking-[.14em] text-zinc-500">{{ $messageDate }}</span>
            </div>
        @endif

        <div class="mb-3 flex {{ $isOutbound ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[82%] rounded-3xl px-4 py-3 shadow-lg {{ $isOutbound ? 'rounded-br-md bg-yellow-300 text-black shadow-yellow-300/10' : 'rounded-bl-md border border-white/10 bg-[#202020] text-zinc-100 shadow-black/20' }}">
                <p class="whitespace-pre-wrap text-sm leading-6">{{ $message->body }}</p>
                <div class="mt-2 flex items-center justify-end gap-2 text-[10px] font-bold {{ $isOutbound ? 'text-black/60' : 'text-zinc-500' }}">
                    <span>{{ ($message->occurred_at ?: $message->created_at)->format('H:i') }}</span>
                    @if ($isOutbound)
                        <span>{{ $message->status === 'failed' ? 'falhou' : 'enviado' }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif
