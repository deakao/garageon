@php
    $cockpitServices = ($services ?? null) ?? $tenant->services()->where('is_active', true)->orderBy('name')->get();
    $cockpitNow = now();
    $saleShouldOpen = old('_form') === 'sale' && $errors->any();
    $saleServiceRows = old('_form') === 'sale' ? old('services', [['service_id' => '', 'quantity' => 1]]) : [['service_id' => '', 'quantity' => 1]];
    $paymentMethods = [
        'debito' => 'Débito',
        'credito' => 'Crédito',
        'pix' => 'Pix',
        'dinheiro' => 'Dinheiro',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferências',
    ];
@endphp

<div data-sale-modal class="fixed inset-0 z-[130] {{ $saleShouldOpen ? 'flex' : 'hidden' }} items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="sale-modal-title">
    <button type="button" data-sale-close class="absolute inset-0 bg-black/80 backdrop-blur-sm" aria-label="Fechar modal de venda"></button>

    <section class="modal-panel relative w-full max-w-2xl rounded-[28px] border border-yellow-300/25 bg-[#101010] text-white shadow-2xl shadow-black/60">
        <div class="modal-panel-scroll">
            <div class="p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                    <div>
                        <p class="font-orbitron text-xs font-black uppercase tracking-[.28em] text-yellow-300">Nova venda</p>
                        <h2 id="sale-modal-title" class="mt-2 font-orbitron text-2xl font-black">Registrar venda</h2>
                        <p data-sale-readable-datetime class="mt-2 text-sm text-zinc-400">Informe cliente, serviço e pagamento.</p>
                    </div>
                    <button type="button" data-sale-close class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/10 text-xl text-zinc-300 transition hover:border-yellow-300 hover:text-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-300" aria-label="Fechar">×</button>
                </div>

                <form method="POST" action="{{ route('sales.store') }}" class="mt-6 grid gap-5">
                    @csrf
                    <input type="hidden" name="_form" value="sale">

                    <div class="grid gap-4 sm:grid-cols-[1fr_160px]">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Data da venda</span>
                            <input type="date" name="sold_date" value="{{ old('sold_date', $cockpitNow->toDateString()) }}" required data-sale-date class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('sold_date') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Horário</span>
                            <input type="time" name="sold_time" value="{{ old('sold_time', $cockpitNow->format('H:i')) }}" required data-sale-time class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                            @error('sold_time') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <section data-vehicle-lookup data-vehicle-lookup-url="{{ route('vehicles.lookup') }}" class="rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Buscar por placa</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-400">Localize o cliente e o veículo na base ou consulte os dados automaticamente.</p>
                            </div>
                            <p data-vehicle-lookup-status class="text-xs font-bold text-zinc-400" aria-live="polite"></p>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <label class="block flex-1">
                                <span class="text-sm font-bold text-zinc-200">Placa</span>
                                <input name="vehicle_plate" value="{{ old('vehicle_plate') }}" required maxlength="10" data-vehicle-plate class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 font-orbitron text-sm font-black uppercase tracking-[.12em] text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="ABC1D23" autocomplete="off">
                                @error('vehicle_plate') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                            <button type="button" data-vehicle-lookup-trigger class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-yellow-300/30 bg-yellow-300/10 px-5 py-3 text-sm font-black text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                Buscar placa
                            </button>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Marca</span>
                                <input name="vehicle_brand" value="{{ old('vehicle_brand') }}" required data-vehicle-brand class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Toyota">
                                @error('vehicle_brand') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Modelo</span>
                                <input name="vehicle_model" value="{{ old('vehicle_model') }}" required data-vehicle-model class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Corolla Cross">
                                @error('vehicle_model') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Ano</span>
                                <input type="number" name="vehicle_year" value="{{ old('vehicle_year') }}" min="1900" max="{{ now()->addYear()->year }}" data-vehicle-year class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="2024">
                                @error('vehicle_year') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-bold text-zinc-200">Cor</span>
                                <input name="vehicle_color" value="{{ old('vehicle_color') }}" data-vehicle-color class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: Preto">
                                @error('vehicle_color') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </section>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Cliente</span>
                            <input name="customer_name" value="{{ old('customer_name') }}" required data-customer-name class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Nome do cliente">
                            @error('customer_name') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">WhatsApp</span>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" required data-customer-phone class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="(11) 99999-9999">
                            @error('customer_phone') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <section class="rounded-3xl border border-yellow-300/15 bg-yellow-300/[.04] p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Usar pontos</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-400">Debite pontos do saldo do cliente nesta venda.</p>
                            </div>
                            <p data-sale-loyalty-balance class="text-xs font-bold text-zinc-400">Busque a placa para ver o saldo.</p>
                        </div>

                        <label class="mt-4 block">
                            <span class="text-sm font-bold text-zinc-200">Pontos a debitar</span>
                            <input type="number" name="loyalty_points_to_debit" value="{{ old('loyalty_points_to_debit', 0) }}" min="0" step="1" data-sale-loyalty-debit class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="0">
                            @error('loyalty_points_to_debit') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </section>

                    <section class="rounded-3xl border border-white/10 bg-black/25 p-4" data-quote-service-editor data-sale-service-editor>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-orbitron text-xs font-black uppercase tracking-[.2em] text-yellow-300">Serviços da venda</p>
                                <p class="mt-1 text-xs text-zinc-400">Adicione todos os serviços vendidos para calcular valor e pontos.</p>
                            </div>
                            <button type="button" data-quote-service-add class="inline-flex items-center justify-center rounded-2xl border border-yellow-300/30 px-4 py-2 text-xs font-black uppercase tracking-[.14em] text-yellow-200 transition hover:bg-yellow-300 hover:text-black focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                Adicionar serviço
                            </button>
                        </div>

                        <div class="mt-4 space-y-3" data-quote-service-list>
                            @foreach ($saleServiceRows as $index => $row)
                                <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/[.035] p-4 sm:grid-cols-[1fr_100px_auto] sm:items-end" data-quote-service-row>
                                    <label class="block">
                                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Serviço</span>
                                        <select name="services[{{ $index }}][service_id]" required data-quote-service-select class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                            <option value="">Escolha o serviço</option>
                                            @foreach ($cockpitServices as $service)
                                                <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-loyalty-points="{{ (int) $service->loyalty_points }}" @selected((int) ($row['service_id'] ?? 0) === $service->id)>{{ $service->name }} · R$ {{ number_format((float) $service->price, 2, ',', '.') }} · +{{ (int) $service->loyalty_points }} pts</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Qtd.</span>
                                        <input type="number" name="services[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" min="1" max="99" required data-quote-service-qty class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                    </label>

                                    <button type="button" data-quote-service-remove class="rounded-xl border border-red-300/25 px-3 py-2.5 text-sm font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover serviço">Remover</button>
                                </div>
                            @endforeach
                        </div>

                        @error('services') <span class="mt-3 block text-xs text-red-300">{{ $message }}</span> @enderror
                        @error('services.*.service_id') <span class="mt-3 block text-xs text-red-300">{{ $message }}</span> @enderror
                        <span data-sale-loyalty-preview class="mt-3 block text-xs font-bold text-yellow-200">Escolha um serviço para ver os pontos da venda.</span>

                        <template data-quote-service-template>
                            <div class="grid gap-3 rounded-2xl border border-white/10 bg-white/[.035] p-4 sm:grid-cols-[1fr_100px_auto] sm:items-end" data-quote-service-row>
                                <label class="block">
                                    <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Serviço</span>
                                    <select name="services[__INDEX__][service_id]" required data-quote-service-select class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                        <option value="">Escolha o serviço</option>
                                        @foreach ($cockpitServices as $service)
                                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-loyalty-points="{{ (int) $service->loyalty_points }}">{{ $service->name }} · R$ {{ number_format((float) $service->price, 2, ',', '.') }} · +{{ (int) $service->loyalty_points }} pts</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="text-xs font-black uppercase tracking-[.14em] text-zinc-400">Qtd.</span>
                                    <input type="number" name="services[__INDEX__][quantity]" value="1" min="1" max="99" required data-quote-service-qty class="mt-2 w-full rounded-xl border border-white/10 bg-black/40 px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                </label>

                                <button type="button" data-quote-service-remove class="rounded-xl border border-red-300/25 px-3 py-2.5 text-sm font-black text-red-200 transition hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300" aria-label="Remover serviço">Remover</button>
                            </div>
                        </template>
                    </section>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Valor pago</span>
                            <input type="number" name="amount" value="{{ old('amount') }}" min="0" step="0.01" required data-sale-amount class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="0,00">
                            @error('amount') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-zinc-200">Forma de pagamento</span>
                            <select name="payment_method" required class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30">
                                <option value="">Escolha a forma</option>
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-zinc-200">Observações</span>
                        <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-yellow-300 focus:ring-2 focus:ring-yellow-300/30" placeholder="Ex: desconto combinado, pacote promocional.">{{ old('notes') }}</textarea>
                        @error('notes') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                    </label>

                    @if ($cockpitServices->isEmpty())
                        <p class="rounded-2xl border border-red-300/20 bg-red-300/10 px-4 py-3 text-sm text-red-100">Cadastre um serviço ativo antes de registrar vendas.</p>
                    @endif

                    <div class="flex flex-col-reverse gap-3 border-t border-white/10 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-sale-close class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-bold text-zinc-200 transition hover:border-white/25 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300">Cancelar</button>
                        <button type="submit" @disabled($cockpitServices->isEmpty()) class="rounded-2xl bg-yellow-300 px-5 py-3 font-orbitron text-sm font-black uppercase tracking-[.16em] text-black transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-yellow-300/20 focus:outline-none focus:ring-2 focus:ring-yellow-300 disabled:cursor-not-allowed disabled:opacity-50">Salvar venda</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
