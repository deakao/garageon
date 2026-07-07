<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat WhatsApp - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing min-h-screen bg-[#070707] text-white antialiased">
    @php
        $quickActions = [
            ['label' => 'Dashboard', 'component' => 'tabler-layout-dashboard', 'href' => route('dashboard'), 'primary' => false],
            ['label' => 'Nova Venda', 'component' => 'tabler-plus', 'overlay' => 'sale-modal', 'primary' => true],
            ['label' => 'Orçamentos', 'component' => 'tabler-file-invoice', 'href' => route('quotes.index'), 'primary' => false],
            ['label' => 'Clientes', 'component' => 'tabler-users', 'href' => route('customers.index'), 'primary' => false],
            ['label' => 'Chat', 'component' => 'tabler-brand-whatsapp', 'href' => route('chat.index'), 'primary' => false],
        ];

        $statusMeta = match ($connection->status) {
            'connected' => ['label' => 'Conectado', 'class' => 'border-emerald-300/25 bg-emerald-300/10 text-emerald-200', 'dot' => 'bg-emerald-300'],
            'qrcode', 'connecting' => ['label' => 'Aguardando QR', 'class' => 'border-yellow-300/25 bg-yellow-300/10 text-yellow-200', 'dot' => 'bg-yellow-300'],
            'error' => ['label' => 'Atenção', 'class' => 'border-red-300/25 bg-red-300/10 text-red-200', 'dot' => 'bg-red-300'],
            'disconnected' => ['label' => 'Desconectado', 'class' => 'border-white/10 bg-white/[.04] text-zinc-300', 'dot' => 'bg-zinc-500'],
            default => ['label' => 'Não configurado', 'class' => 'border-white/10 bg-white/[.04] text-zinc-300', 'dot' => 'bg-zinc-500'],
        };

        $selectedCustomerModel = $selectedConversation?->customer ?: $selectedCustomer;
        $selectedName = $selectedConversation?->contact_name ?: $selectedCustomerModel?->name;
        $selectedPhone = $selectedConversation?->contact_phone ?: preg_replace('/\D+/', '', (string) $selectedCustomerModel?->phone);
        $selectedVehicle = $selectedCustomerModel?->vehicles?->first();
        $selectedInitials = collect(explode(' ', trim((string) $selectedName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') ?: 'GO';
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

    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(250,204,21,.20),transparent_25%),radial-gradient(circle_at_90%_8%,rgba(34,197,94,.12),transparent_20%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.045] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-[1800px]">
            @include('garageon.dashboard.header')

            <p data-whatsapp-status-message class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100 {{ session('status') ? '' : 'hidden' }}">{{ session('status') }}</p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-300/25 bg-red-300/10 px-5 py-4 text-sm text-red-100">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <section class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
                <div class="rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Central WhatsApp</p>
                            <h1 class="mt-3 font-orbitron text-3xl font-black text-white sm:text-4xl">Atendimento em tempo real</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">Converse com clientes, acompanhe retornos e mantenha o histórico comercial dentro do cockpit.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[520px]">
                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="text-xs font-black uppercase tracking-[.16em] text-zinc-500">Conversas</p>
                                <strong data-chat-stat="total_conversations" class="mt-2 block font-orbitron text-2xl font-black">{{ number_format($chatStats['total_conversations'], 0, ',', '.') }}</strong>
                            </article>
                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="text-xs font-black uppercase tracking-[.16em] text-zinc-500">Não lidas</p>
                                <strong data-chat-stat="unread" class="mt-2 block font-orbitron text-2xl font-black text-yellow-300">{{ number_format($chatStats['unread'], 0, ',', '.') }}</strong>
                            </article>
                            <article class="rounded-2xl border border-white/10 bg-black/35 p-4">
                                <p class="text-xs font-black uppercase tracking-[.16em] text-zinc-500">Hoje</p>
                                <strong data-chat-stat="messages_today" class="mt-2 block font-orbitron text-2xl font-black">{{ number_format($chatStats['messages_today'], 0, ',', '.') }}</strong>
                            </article>
                        </div>
                    </div>
                </div>

                <aside data-whatsapp-connection data-status="{{ $connection->status }}" data-connect-url="{{ route('chat.connect') }}" data-disconnect-url="{{ route('chat.disconnect') }}" data-renew-qr-url="{{ route('chat.qr.renew') }}" data-sync-url="{{ route('chat.sync') }}" class="rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.22em] text-yellow-300">Evolution Go</p>
                            <h2 class="mt-2 text-xl font-black">Conexão da loja</h2>
                        </div>
                        <span data-whatsapp-status-badge class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-black {{ $statusMeta['class'] }}">
                            <span data-whatsapp-status-dot class="h-2 w-2 rounded-full {{ $statusMeta['dot'] }}"></span>
                            <span data-whatsapp-status-label>{{ $statusMeta['label'] }}</span>
                        </span>
                    </div>

                    <p data-whatsapp-connection-copy class="mt-5 text-sm leading-6 text-zinc-400">{{ $connection->status === 'connected' ? 'WhatsApp conectado. Desconecte apenas se quiser trocar o aparelho da loja.' : 'Clique em conectar e escaneie o QR Code pelo WhatsApp da loja. O GarageON prepara tudo nos bastidores.' }}</p>

                    @if (! $evolutionConfigured)
                        <p class="mt-4 rounded-2xl border border-red-300/20 bg-red-300/10 px-4 py-3 text-sm leading-6 text-red-100">Configure `EVOLUTION_GO_URL` e `EVOLUTION_GO_API_KEY` no `.env` para ativar a integração.</p>
                    @endif

                    <form data-whatsapp-connection-form method="POST" action="{{ $connection->status === 'connected' ? route('chat.disconnect') : route('chat.connect') }}" class="mt-5">
                        @csrf
                        @if ($connection->status === 'connected')
                            @method('DELETE')
                        @endif
                        <button data-whatsapp-connection-button @disabled(! $evolutionConfigured) class="w-full cursor-pointer rounded-2xl px-5 py-4 font-orbitron text-sm font-black uppercase tracking-[.16em] transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50 {{ $connection->status === 'connected' ? 'border border-red-300/30 bg-red-300/10 text-red-100 hover:border-red-200 hover:bg-red-300/20 focus:ring-red-300' : 'bg-yellow-300 text-black hover:bg-white focus:ring-yellow-300' }}">{{ $connection->status === 'connected' ? 'Desconectar' : 'Conectar' }}</button>
                    </form>

                    <div data-whatsapp-qrcode-panel class="mt-5 hidden rounded-3xl border border-yellow-300/20 bg-black/35 p-4 text-center">
                            <img data-whatsapp-qrcode alt="QR Code para conectar WhatsApp" class="mx-auto h-48 w-48 rounded-2xl bg-white p-2">
                            <p class="mt-3 text-xs leading-5 text-zinc-400">Abra o WhatsApp no celular, acesse dispositivos conectados e escaneie o QR Code.</p>
                            <button type="button" data-whatsapp-renew-qr class="mt-4 inline-flex cursor-pointer items-center justify-center rounded-2xl border border-yellow-300/30 bg-yellow-300/10 px-4 py-2 font-orbitron text-xs font-black uppercase tracking-[.16em] text-yellow-100 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">Renovar QR</button>
                    </div>
                </aside>
            </section>

            <section class="mt-6 overflow-hidden rounded-[32px] border border-white/10 bg-[#101010]/95 shadow-2xl shadow-black/40 backdrop-blur" data-whatsapp-chat data-chat-stream-url="{{ route('chat.stream') }}" data-chat-selected="{{ $selectedConversation?->id ?? '' }}">
                <div class="grid min-h-[720px] lg:grid-cols-[380px_minmax(0,1fr)]">
                    <aside class="border-b border-white/10 bg-[#0d0d0d] lg:border-b-0 lg:border-r lg:border-white/10">
                        <div class="border-b border-white/10 p-4">
                            <div class="flex items-center gap-3">
                                <div class="grid h-12 w-12 place-items-center rounded-full bg-yellow-300 text-black">
                                    <x-tabler-brand-whatsapp class="h-6 w-6" stroke-width="2.4" />
                                </div>
                                <div>
                                    <h2 class="font-orbitron text-lg font-black">Inbox</h2>
                                    <p class="text-xs text-zinc-500">Clientes e conversas WhatsApp</p>
                                </div>
                            </div>
                            <label class="mt-4 block">
                                <span class="sr-only">Buscar conversa</span>
                                <input data-chat-search placeholder="Buscar cliente ou telefone" class="w-full rounded-2xl border border-white/10 bg-black/45 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            </label>
                        </div>

                        <div class="max-h-[620px] overflow-y-auto p-2" data-chat-list>
                            @include('garageon.chat._list')
                        </div>
                    </aside>

                    <article class="flex min-h-[720px] flex-col bg-[#0f0f0f]">
                        @if ($selectedName)
                            <header class="flex items-center justify-between gap-4 border-b border-white/10 bg-[#141414] px-4 py-4 sm:px-6">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-yellow-300 text-sm font-black text-black">{{ $selectedInitials }}</div>
                                    <div class="min-w-0">
                                        <h2 class="truncate text-lg font-black text-white">{{ $selectedName }}</h2>
                                        <p class="truncate text-xs text-zinc-400">{{ $formatPhone($selectedPhone) }} @if ($selectedVehicle) · {{ trim($selectedVehicle->brand.' '.$selectedVehicle->model) }} @endif</p>
                                    </div>
                                </div>
                                <div class="hidden items-center gap-2 rounded-full border border-white/10 bg-black/30 px-3 py-2 text-xs font-black text-zinc-300 sm:inline-flex">
                                    <span data-whatsapp-channel-dot class="h-2 w-2 rounded-full {{ $connection->status === 'connected' ? 'bg-emerald-300' : 'bg-zinc-500' }}"></span>
                                    <span data-whatsapp-channel-label>{{ $connection->status === 'connected' ? 'Canal ON' : 'Canal offline' }}</span>
                                </div>
                            </header>

                            <div class="relative flex-1 overflow-hidden">
                                <div class="pointer-events-none absolute inset-0 opacity-[.07] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,.9)_1px,transparent_0)] [background-size:28px_28px]"></div>
                                <div class="relative max-h-[560px] min-h-[560px] overflow-y-auto px-4 py-5 sm:px-6" data-chat-messages>
                                    @include('garageon.chat._messages')
                                </div>
                            </div>

                            <form method="POST" action="{{ route('chat.messages.store') }}" class="border-t border-white/10 bg-[#141414] p-4 sm:p-5">
                                @csrf
                                @if ($selectedConversation)
                                    <input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">
                                @elseif ($selectedCustomer)
                                    <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
                                @endif

                                @if ($connection->status !== 'connected')
                                    <p data-whatsapp-send-warning class="mb-3 rounded-2xl border border-yellow-300/20 bg-yellow-300/10 px-4 py-3 text-xs font-bold text-yellow-100">Conecte o WhatsApp para liberar o envio direto pela Evolution.</p>
                                @else
                                    <p data-whatsapp-send-warning class="mb-3 hidden rounded-2xl border border-yellow-300/20 bg-yellow-300/10 px-4 py-3 text-xs font-bold text-yellow-100">Conecte o WhatsApp para liberar o envio direto pela Evolution.</p>
                                @endif

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                    <label class="block flex-1">
                                        <span class="sr-only">Mensagem</span>
                                        <textarea data-whatsapp-send-control name="body" rows="2" required @disabled($connection->status !== 'connected') class="w-full resize-none rounded-3xl border border-white/10 bg-black/45 px-5 py-4 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30 disabled:cursor-not-allowed disabled:opacity-60" placeholder="Digite uma mensagem"></textarea>
                                    </label>
                                    <button data-whatsapp-send-control @disabled($connection->status !== 'connected') class="inline-flex items-center justify-center gap-2 rounded-3xl bg-yellow-300 px-6 py-4 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">
                                        <x-tabler-send class="h-5 w-5" stroke-width="2.4" />
                                        Enviar
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="grid flex-1 place-items-center p-8 text-center">
                                <div class="max-w-md">
                                    <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-yellow-300 text-black">
                                        <x-tabler-users class="h-8 w-8" stroke-width="2.4" />
                                    </div>
                                    <h2 class="mt-5 font-orbitron text-2xl font-black">Nenhum cliente para conversar</h2>
                                    <p class="mt-3 text-sm leading-6 text-zinc-400">Cadastre clientes com WhatsApp para iniciar conversas direto pelo cockpit.</p>
                                    <a href="{{ route('customers.index') }}" class="mt-5 inline-flex rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Ir para clientes</a>
                                </div>
                            </div>
                        @endif
                    </article>
                </div>
            </section>
        </div>
    </main>

    <script>
        document.querySelectorAll('[data-chat-messages]').forEach((panel) => {
            panel.scrollTop = panel.scrollHeight;
        });

        document.querySelector('[data-chat-search]')?.addEventListener('input', (event) => {
            const term = event.target.value.trim().toLowerCase();

            document.querySelectorAll('[data-chat-item]').forEach((item) => {
                item.classList.toggle('hidden', term.length > 0 && ! item.dataset.chatSearchText.includes(term));
            });
        });

        const applyChatSearch = () => {
            const term = document.querySelector('[data-chat-search]')?.value.trim().toLowerCase() || '';

            document.querySelectorAll('[data-chat-item]').forEach((item) => {
                item.classList.toggle('hidden', term.length > 0 && ! item.dataset.chatSearchText.includes(term));
            });
        };

        const chatPanel = document.querySelector('[data-whatsapp-chat]');

        if (chatPanel?.dataset.chatStreamUrl) {
            const listEl = document.querySelector('[data-chat-list]');
            const messagesEl = document.querySelector('[data-chat-messages]');
            const selectedId = chatPanel.dataset.chatSelected || '';

            const streamUrl = () => {
                const url = new URL(chatPanel.dataset.chatStreamUrl, window.location.origin);
                if (selectedId) {
                    url.searchParams.set('conversation', selectedId);
                }
                return url.toString();
            };

            const refresh = async () => {
                if (document.hidden) {
                    return;
                }

                let data;
                try {
                    const response = await fetch(streamUrl(), { headers: { 'Accept': 'application/json' } });
                    if (! response.ok) {
                        return;
                    }
                    data = await response.json();
                } catch (error) {
                    return;
                }

                if (listEl && typeof data.list === 'string') {
                    listEl.innerHTML = data.list;
                    applyChatSearch();
                }

                if (messagesEl && typeof data.messages === 'string') {
                    const nearBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;
                    messagesEl.innerHTML = data.messages;
                    if (nearBottom) {
                        messagesEl.scrollTop = messagesEl.scrollHeight;
                    }
                }

                if (data.stats) {
                    Object.entries(data.stats).forEach(([key, value]) => {
                        const el = document.querySelector(`[data-chat-stat="${key}"]`);
                        if (el) {
                            el.textContent = new Intl.NumberFormat('pt-BR').format(value);
                        }
                    });
                }
            };

            window.setInterval(refresh, 5000);
            document.addEventListener('visibilitychange', () => {
                if (! document.hidden) {
                    refresh();
                }
            });
        }

        const connectionCard = document.querySelector('[data-whatsapp-connection]');

        if (connectionCard) {
            const csrfToken = @json(csrf_token());
            const form = connectionCard.querySelector('[data-whatsapp-connection-form]');
            const button = connectionCard.querySelector('[data-whatsapp-connection-button]');
            const statusMessage = document.querySelector('[data-whatsapp-status-message]');
            const badge = connectionCard.querySelector('[data-whatsapp-status-badge]');
            const dot = connectionCard.querySelector('[data-whatsapp-status-dot]');
            const label = connectionCard.querySelector('[data-whatsapp-status-label]');
            const copy = connectionCard.querySelector('[data-whatsapp-connection-copy]');
            const qrPanel = connectionCard.querySelector('[data-whatsapp-qrcode-panel]');
            const qrImage = connectionCard.querySelector('[data-whatsapp-qrcode]');
            const renewQrButton = connectionCard.querySelector('[data-whatsapp-renew-qr]');
            const channelDot = document.querySelector('[data-whatsapp-channel-dot]');
            const channelLabel = document.querySelector('[data-whatsapp-channel-label]');
            const sendWarning = document.querySelector('[data-whatsapp-send-warning]');
            const sendControls = document.querySelectorAll('[data-whatsapp-send-control]');
            const statusMeta = {
                connected: ['Conectado', 'border-emerald-300/25 bg-emerald-300/10 text-emerald-200', 'bg-emerald-300'],
                qrcode: ['Aguardando QR', 'border-yellow-300/25 bg-yellow-300/10 text-yellow-200', 'bg-yellow-300'],
                connecting: ['Aguardando QR', 'border-yellow-300/25 bg-yellow-300/10 text-yellow-200', 'bg-yellow-300'],
                error: ['Atenção', 'border-red-300/25 bg-red-300/10 text-red-200', 'bg-red-300'],
                disconnected: ['Desconectado', 'border-white/10 bg-white/[.04] text-zinc-300', 'bg-zinc-500'],
                unconfigured: ['Não configurado', 'border-white/10 bg-white/[.04] text-zinc-300', 'bg-zinc-500'],
            };
            let statusPoll;

            const request = async (url, method) => {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw data;
                }

                return data;
            };

            const showMessage = (message, error = false) => {
                if (! statusMessage || ! message) {
                    return;
                }

                statusMessage.textContent = message;
                statusMessage.classList.remove('hidden', 'border-red-300/25', 'bg-red-300/10', 'text-red-100', 'border-yellow-300/25', 'bg-yellow-300/10', 'text-yellow-100');
                statusMessage.classList.add(...(error ? ['border-red-300/25', 'bg-red-300/10', 'text-red-100'] : ['border-yellow-300/25', 'bg-yellow-300/10', 'text-yellow-100']));
            };

            const setActionButton = (connected) => {
                button.textContent = connected ? 'Desconectar' : 'Conectar';
                button.className = connected
                    ? 'w-full cursor-pointer rounded-2xl border border-red-300/30 bg-red-300/10 px-5 py-4 font-orbitron text-sm font-black uppercase tracking-[.16em] text-red-100 transition hover:border-red-200 hover:bg-red-300/20 focus:outline-none focus:ring-2 focus:ring-red-300 disabled:cursor-not-allowed disabled:opacity-50'
                    : 'w-full cursor-pointer rounded-2xl bg-yellow-300 px-5 py-4 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50';
            };

            const applyState = (data) => {
                const status = data.status || 'unconfigured';
                const connected = status === 'connected' || data.connected === true;
                const meta = statusMeta[status] || statusMeta.unconfigured;
                connectionCard.dataset.status = status;

                label.textContent = meta[0];
                badge.className = `inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-black ${meta[1]}`;
                dot.className = `h-2 w-2 rounded-full ${meta[2]}`;
                copy.textContent = connected
                    ? 'WhatsApp conectado. Desconecte apenas se quiser trocar o aparelho da loja.'
                    : 'Clique em conectar e escaneie o QR Code pelo WhatsApp da loja. O GarageON prepara tudo nos bastidores.';

                setActionButton(connected);

                if (qrPanel && qrImage) {
                    const canShowQr = ['qrcode', 'connecting'].includes(status) && ! connected;

                    if (data.qrcode && qrImage.getAttribute('src') !== data.qrcode) {
                        qrImage.src = data.qrcode;
                    }

                    if (! canShowQr) {
                        qrImage.removeAttribute('src');
                    }

                    qrPanel.classList.toggle('hidden', ! canShowQr || ! qrImage.getAttribute('src'));
                }

                channelDot?.classList.toggle('bg-emerald-300', connected);
                channelDot?.classList.toggle('bg-zinc-500', ! connected);
                if (channelLabel) {
                    channelLabel.textContent = connected ? 'Canal ON' : 'Canal offline';
                }

                sendWarning?.classList.toggle('hidden', connected);
                sendControls.forEach((control) => {
                    control.disabled = ! connected;
                });

                if (data.message) {
                    showMessage(data.message);
                }

                if (['qrcode', 'connecting'].includes(status)) {
                    startPolling(4000);
                } else if (connected) {
                    startPolling(60000);
                } else {
                    stopPolling();
                }
            };

            const sync = async () => {
                try {
                    applyState(await request(connectionCard.dataset.syncUrl, 'POST'));
                } catch (error) {
                    showMessage(error.message || 'Não consegui atualizar o status do WhatsApp agora.', true);
                    stopPolling();
                }
            };

            let pollInterval;

            const startPolling = (intervalMs) => {
                if (statusPoll && pollInterval === intervalMs) {
                    return;
                }
                stopPolling();
                pollInterval = intervalMs;
                statusPoll = window.setInterval(sync, intervalMs);
            };

            const stopPolling = () => {
                if (statusPoll) {
                    window.clearInterval(statusPoll);
                    statusPoll = null;
                    pollInterval = null;
                }
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const connected = connectionCard.dataset.status === 'connected';
                button.disabled = true;
                button.textContent = connected ? 'Desconectando...' : 'Conectando...';

                try {
                    applyState(await request(connected ? connectionCard.dataset.disconnectUrl : connectionCard.dataset.connectUrl, connected ? 'DELETE' : 'POST'));
                } catch (error) {
                    showMessage(error.message || 'Não consegui concluir essa ação. Tente novamente.', true);
                } finally {
                    button.disabled = false;
                    setActionButton(connectionCard.dataset.status === 'connected');
                }
            });

            renewQrButton?.addEventListener('click', async () => {
                renewQrButton.disabled = true;
                renewQrButton.textContent = 'Renovando...';

                try {
                    applyState(await request(connectionCard.dataset.renewQrUrl, 'POST'));
                } catch (error) {
                    showMessage(error.message || 'Não consegui renovar o QR Code agora.', true);
                } finally {
                    renewQrButton.disabled = false;
                    renewQrButton.textContent = 'Renovar QR';
                }
            });

            if (['qrcode', 'connecting'].includes(connectionCard.dataset.status)) {
                startPolling(4000);
            } else if (connectionCard.dataset.status === 'connected') {
                startPolling(60000);
            }
        }
    </script>
</body>
</html>
