<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#070707] text-white antialiased">
    <main class="relative min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(250,204,21,.18),transparent_25%),radial-gradient(circle_at_100%_10%,rgba(255,255,255,.10),transparent_24%),linear-gradient(180deg,rgba(255,255,255,.04),transparent_44%)]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[.05] [background-image:linear-gradient(rgba(255,255,255,.9)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.9)_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div class="relative mx-auto max-w-7xl">
            @include('garageon.dashboard.header')

            @if (session('status'))
                <p class="mt-5 rounded-2xl border border-yellow-300/25 bg-yellow-300/10 px-5 py-4 text-sm font-bold text-yellow-100">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-300/25 bg-red-300/10 px-5 py-4 text-sm text-red-100">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <section class="mt-6 overflow-hidden rounded-[32px] border border-white/10 bg-[#101010]/95 shadow-2xl shadow-black/30 backdrop-blur">
                <div class="grid divide-y divide-white/10 lg:grid-cols-3 lg:divide-x lg:divide-y-0 lg:divide-white/10">
                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-users class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Total de clientes</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($customerStats['total'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">↑ {{ $customerStats['new_this_month'] }} este mês</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-car class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Com veículo</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($customerStats['with_vehicles'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-zinc-400">histórico pronto para venda</span>
                        </div>
                    </article>

                    <article class="flex items-center gap-5 p-6 sm:p-8">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-yellow-300/15 text-yellow-300 shadow-lg shadow-yellow-300/10">
                            <x-tabler-calendar-check class="h-10 w-10" stroke-width="2.2" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-zinc-500">Ativos hoje</p>
                            <strong class="mt-1 block font-orbitron text-3xl font-black text-white">{{ number_format($customerStats['active_today'], 0, ',', '.') }}</strong>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-black text-yellow-300">agenda em movimento</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-8 rounded-[32px] border border-white/10 bg-[#101010]/95 p-5 shadow-2xl shadow-black/30 sm:p-8">
                <div class="mb-6 grid gap-5 lg:grid-cols-[1fr_minmax(320px,520px)_auto] lg:items-start">
                    <div>
                        <h1 class="font-orbitron text-2xl font-black text-white">Clientes</h1>
                    </div>
                    <button type="button" data-modal-open="customer-create-modal" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 lg:justify-self-end">
                        Novo cliente
                    </button>
                </div>

                <div class="customers-datatable">
                    <table data-customers-table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Veículo</th>
                                <th>WhatsApp</th>
                                <th>E-mail</th>
                                <th>Entrada</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php
                                    $mainVehicle = $customer->vehicles->first();
                                @endphp
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="block text-sm font-black text-white">{{ $customer->name }}</strong>
                                            <span class="mt-1 block text-xs text-zinc-500">Cliente #{{ $customer->id }}</span>
                                            @if ($customer->vehicles->isNotEmpty())
                                                <span class="sr-only">Veículos: {{ $customer->vehicles->map(fn ($vehicle) => trim($vehicle->plate.' '.$vehicle->brand.' '.$vehicle->model))->join(', ') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="block text-sm font-black text-zinc-100">{{ $mainVehicle ? trim($mainVehicle->brand.' '.$mainVehicle->model) : 'Sem veículo' }}</span>
                                            <span class="mt-1 block text-xs text-zinc-500">{{ $mainVehicle?->plate ?? 'Placa não informada' }}</span>
                                            @if ($mainVehicle && ($mainVehicle->year || $mainVehicle->color))
                                                <span class="mt-1 block text-xs text-zinc-500">{{ collect([$mainVehicle->year, $mainVehicle->color])->filter()->join(' · ') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>{{ $customer->email ?? 'E-mail não informado' }}</td>
                                    <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button type="button" data-modal-open="customer-edit-{{ $customer->id }}" class="rounded-xl border border-yellow-300/30 px-3 py-2 text-xs font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                                Editar
                                            </button>
                                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Excluir este cliente? Isso também remove históricos vinculados a ele.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-xl border border-red-300/30 px-3 py-2 text-xs font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <dialog id="customer-create-modal" class="customer-modal w-[min(94vw,980px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                <form method="POST" action="{{ route('customers.store') }}" class="p-6 sm:p-8">
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Novo cliente</p>
                            <h2 class="mt-2 text-2xl font-black">Cadastrar contato</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-400">Adicione o cliente à base para acompanhar agenda, veículos e oportunidades.</p>
                        </div>
                        <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                    </div>

                    <div class="mt-5 flex gap-2 rounded-2xl border border-white/10 bg-black/25 p-1">
                        <button type="button" data-tab-target="customer-create-details" class="flex-1 rounded-xl bg-yellow-300 px-4 py-2.5 text-sm font-black text-black transition" aria-selected="true">Dados</button>
                        <button type="button" data-tab-target="customer-create-vehicles" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-black text-zinc-300 transition hover:text-yellow-300" aria-selected="false">Veículos</button>
                    </div>

                    <div id="customer-create-details" class="mt-6 grid gap-4" data-tab-panel>
                        @include('garageon.customers.form-fields', ['customer' => null])
                    </div>

                    <div id="customer-create-vehicles" class="mt-6 hidden" data-tab-panel>
                        @include('garageon.customers.vehicle-fields', ['customer' => null])
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-modal-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                        <button class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar cliente</button>
                    </div>
                </form>
            </dialog>

            @foreach ($customers as $customer)
                <dialog id="customer-edit-{{ $customer->id }}" class="customer-modal w-[min(94vw,980px)] rounded-[28px] border border-yellow-300/25 bg-[#101010] p-0 text-white shadow-2xl shadow-black/70 backdrop:bg-black/80 backdrop:backdrop-blur-sm">
                    <form method="POST" action="{{ route('customers.update', $customer) }}" class="p-6 sm:p-8">
                        @csrf
                        @method('PUT')
                        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Editar cliente</p>
                                <h2 class="mt-2 text-2xl font-black">{{ $customer->name }}</h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-400">Atualize os dados usados em agenda, histórico e campanhas.</p>
                            </div>
                            <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                        </div>

                        <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-white/10 bg-black/25 p-1 sm:flex-row">
                            <button type="button" data-tab-target="customer-edit-{{ $customer->id }}-details" class="flex-1 cursor-pointer rounded-xl bg-yellow-300 px-4 py-2.5 text-sm font-black text-black transition" aria-selected="true">Dados</button>
                            <button type="button" data-tab-target="customer-edit-{{ $customer->id }}-vehicles" class="flex-1 cursor-pointer rounded-xl px-4 py-2.5 text-sm font-black text-zinc-300 transition hover:text-yellow-300" aria-selected="false">Veículos</button>
                            <button type="button" data-tab-target="customer-edit-{{ $customer->id }}-history" class="flex-1 cursor-pointer rounded-xl px-4 py-2.5 text-sm font-black text-zinc-300 transition hover:text-yellow-300" aria-selected="false">Histórico</button>
                        </div>

                        <div id="customer-edit-{{ $customer->id }}-details" class="mt-6 grid gap-4" data-tab-panel>
                            @include('garageon.customers.form-fields', ['customer' => $customer])
                        </div>

                        <div id="customer-edit-{{ $customer->id }}-vehicles" class="mt-6 hidden" data-tab-panel>
                            @include('garageon.customers.vehicle-fields', ['customer' => $customer])
                        </div>

                        <div id="customer-edit-{{ $customer->id }}-history" class="mt-6 hidden" data-tab-panel>
                            @include('garageon.customers.history', ['customer' => $customer])
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                            <button type="button" data-modal-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                            <button class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Salvar alterações</button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        </div>
    </main>
</body>
</html>
