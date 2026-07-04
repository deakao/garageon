import { DataTable } from 'simple-datatables';
import 'simple-datatables/dist/style.css';

const initGarageDatatable = (table, options) => {
    new DataTable(table, options);
};

const overlaySelectors = {
    'sale-modal': '[data-sale-modal]',
    'appointment-modal': '[data-appointment-modal]',
    'quote-modal': '[data-quote-modal]',
};

const openOverlay = (name) => {
    const modal = document.querySelector(overlaySelectors[name]);

    if (! modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');

    if (name === 'sale-modal') {
        refreshSaleDatetimeLabel();
        modal.querySelector('[data-customer-name]')?.focus();
    }

    if (name === 'quote-modal') {
        refreshQuoteDatetimeLabel();
        refreshQuoteTotal();
        modal.querySelector('[data-customer-name]')?.focus();
    }
};

const closeOverlay = (modal) => {
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');

    if (! document.querySelector(`${Object.values(overlaySelectors).join(',')}:not(.hidden)`)) {
        document.body.classList.remove('overflow-hidden');
    }
};

const closeAllOverlays = () => {
    Object.values(overlaySelectors).forEach((selector) => {
        document.querySelectorAll(selector).forEach((modal) => closeOverlay(modal));
    });
};

const formatSaleDatetime = (date, time) => {
    const parsed = new Date(`${date}T${time}:00`);

    return parsed.toLocaleString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const refreshSaleDatetimeLabel = () => {
    const saleModal = document.querySelector('[data-sale-modal]');
    const dateInput = saleModal?.querySelector('[data-sale-date]');
    const timeInput = saleModal?.querySelector('[data-sale-time]');
    const label = saleModal?.querySelector('[data-sale-readable-datetime]');

    if (! dateInput?.value || ! timeInput?.value || ! label) {
        return;
    }

    label.textContent = `Venda em ${formatSaleDatetime(dateInput.value, timeInput.value)}`;
};

const refreshQuoteDatetimeLabel = () => {
    const quoteModal = document.querySelector('[data-quote-modal]');
    const dateInput = quoteModal?.querySelector('[data-quote-date]');
    const timeInput = quoteModal?.querySelector('[data-quote-time]');
    const label = quoteModal?.querySelector('[data-quote-readable-datetime]');

    if (! dateInput?.value || ! timeInput?.value || ! label) {
        return;
    }

    label.textContent = `Orçamento em ${formatSaleDatetime(dateInput.value, timeInput.value)}`;
};

const formatQuoteMoney = (value) => new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
}).format(value);

const formatCurrencyInput = (input) => {
    const digits = input.value.replace(/\D/g, '');

    if (! digits) {
        input.value = '';

        return;
    }

    input.value = formatQuoteMoney(Number(digits) / 100);
};

const refreshQuoteTotalForEditor = (editor) => {
    const totalEl = editor?.querySelector('[data-quote-total]');

    if (! totalEl) {
        return;
    }

    let total = 0;

    editor.querySelectorAll('[data-quote-service-row]').forEach((row) => {
        const select = row.querySelector('[data-quote-service-select]');
        const qtyInput = row.querySelector('[data-quote-service-qty]');
        const price = Number(select?.selectedOptions[0]?.dataset.price ?? 0);
        const qty = Number(qtyInput?.value ?? 1);

        if (select?.value && qty > 0) {
            total += price * qty;
        }
    });

    totalEl.textContent = formatQuoteMoney(total);
};

const refreshAllQuoteTotals = () => {
    document.querySelectorAll('[data-quote-service-editor]').forEach(refreshQuoteTotalForEditor);
};

const refreshQuoteTotal = () => refreshAllQuoteTotals();

const reindexQuoteServiceRows = (editor) => {
    editor?.querySelectorAll('[data-quote-service-row]').forEach((row, index) => {
        row.querySelector('[data-quote-service-select]')?.setAttribute('name', `services[${index}][service_id]`);
        row.querySelector('[data-quote-service-qty]')?.setAttribute('name', `services[${index}][quantity]`);
    });
};

const initVehicleLookup = (lookup) => {
    const url = lookup.dataset.vehicleLookupUrl;
    const plate = lookup.querySelector('[data-vehicle-plate]');
    const brand = lookup.querySelector('[data-vehicle-brand]');
    const model = lookup.querySelector('[data-vehicle-model]');
    const year = lookup.querySelector('[data-vehicle-year]');
    const color = lookup.querySelector('[data-vehicle-color]');
    const customerName = lookup.closest('form')?.querySelector('[data-customer-name]');
    const customerPhone = lookup.closest('form')?.querySelector('[data-customer-phone]');
    const trigger = lookup.querySelector('[data-vehicle-lookup-trigger]');
    const status = lookup.querySelector('[data-vehicle-lookup-status]');
    let lookupTimer;
    let controller;

    const setStatus = (message, tone = 'neutral') => {
        if (! status) {
            return;
        }

        status.textContent = message;
        status.classList.toggle('text-yellow-300', tone === 'loading');
        status.classList.toggle('text-emerald-300', tone === 'success');
        status.classList.toggle('text-red-300', tone === 'error');
        status.classList.toggle('text-zinc-400', tone === 'neutral');
    };

    const applyVehiclePayload = (vehicle) => {
        if (vehicle.brand) {
            brand.value = vehicle.brand;
        }

        if (vehicle.model) {
            model.value = vehicle.model;
        }

        if (vehicle.year) {
            year.value = vehicle.year;
        }

        if (vehicle.color) {
            color.value = vehicle.color;
        }

        if (vehicle.customer_name && customerName) {
            customerName.value = vehicle.customer_name;
        }

        if (vehicle.customer_phone && customerPhone) {
            customerPhone.value = vehicle.customer_phone;
        }

        if (vehicle.source === 'garageon') {
            setStatus('Cliente e veículo encontrados na base. Confira antes de salvar.', 'success');
        } else {
            setStatus('Dados do veículo encontrados. Confira antes de salvar.', 'success');
        }
    };

    const runLookup = async () => {
        const normalizedPlate = plate.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        plate.value = normalizedPlate;

        if (normalizedPlate.length < 7) {
            setStatus('Informe uma placa válida com 7 caracteres.', 'error');

            return;
        }

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        setStatus('Buscando placa...', 'loading');

        try {
            const response = await fetch(`${url}?plate=${encodeURIComponent(normalizedPlate)}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });

            if (! response.ok) {
                throw new Error('lookup_failed');
            }

            applyVehiclePayload(await response.json());
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setStatus('Não encontrei agora. Preencha manualmente.', 'error');
        }
    };

    plate?.addEventListener('input', () => {
        const normalizedPlate = plate.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        plate.value = normalizedPlate;
        window.clearTimeout(lookupTimer);

        if (controller) {
            controller.abort();
        }

        if (normalizedPlate.length < 7) {
            setStatus('');

            return;
        }

        lookupTimer = window.setTimeout(runLookup, 500);
    });

    trigger?.addEventListener('click', runLookup);
    plate?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(lookupTimer);
            runLookup();
        }
    });
};

document.querySelectorAll('[data-customers-table]').forEach((table) => {
    initGarageDatatable(table, {
        searchable: true,
        sortable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [10, 15, 25, 50, ['Todos', -1]],
        firstLast: true,
        nextPrev: true,
        firstText: 'Primeira',
        lastText: 'Última',
        nextText: 'Próxima',
        prevText: 'Anterior',
        labels: {
            placeholder: 'Buscar cliente, WhatsApp, e-mail ou veículo...',
            searchTitle: 'Buscar na base de clientes',
            perPage: 'clientes por página',
            noRows: 'Nenhum cliente cadastrado ainda',
            noResults: 'Nenhum cliente combina com essa busca',
            info: 'Mostrando {start} a {end} de {rows} clientes',
        },
        columns: [
            { select: 4, type: 'date', format: 'DD/MM/YYYY', sortSequence: ['desc', 'asc'] },
            { select: 5, sortable: false },
        ],
    });
});

document.querySelectorAll('[data-services-table]').forEach((table) => {
    initGarageDatatable(table, {
        searchable: true,
        sortable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [10, 15, 25, 50, ['Todos', -1]],
        firstLast: true,
        nextPrev: true,
        firstText: 'Primeira',
        lastText: 'Última',
        nextText: 'Próxima',
        prevText: 'Anterior',
        labels: {
            placeholder: 'Buscar serviço, categoria ou preço...',
            searchTitle: 'Buscar no catálogo de serviços',
            perPage: 'serviços por página',
            noRows: 'Nenhum serviço cadastrado ainda',
            noResults: 'Nenhum serviço combina com essa busca',
            info: 'Mostrando {start} a {end} de {rows} serviços',
        },
        columns: [
            { select: 5, sortable: false },
        ],
    });
});

document.querySelectorAll('[data-quotes-table]').forEach((table) => {
    initGarageDatatable(table, {
        searchable: true,
        sortable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [10, 15, 25, 50, ['Todos', -1]],
        firstLast: true,
        nextPrev: true,
        firstText: 'Primeira',
        lastText: 'Última',
        nextText: 'Próxima',
        prevText: 'Anterior',
        labels: {
            placeholder: 'Buscar cliente, placa, status ou valor...',
            searchTitle: 'Buscar nos orçamentos',
            perPage: 'orçamentos por página',
            noRows: 'Nenhum orçamento cadastrado ainda',
            noResults: 'Nenhum orçamento combina com essa busca',
            info: 'Mostrando {start} a {end} de {rows} orçamentos',
        },
        columns: [
            { select: 6, type: 'date', format: 'DD/MM/YYYY', sortSequence: ['desc', 'asc'] },
            { select: 7, sortable: false },
        ],
    });
});

document.querySelectorAll('[data-vehicle-lookup]').forEach(initVehicleLookup);

document.querySelectorAll('[data-currency-mask]').forEach((input) => {
    input.addEventListener('input', () => formatCurrencyInput(input));
});

document.querySelector('[data-sale-service-select]')?.addEventListener('change', (event) => {
    const option = event.target.selectedOptions[0];
    const amountInput = document.querySelector('[data-sale-amount]');

    if (! option?.dataset.price || ! amountInput) {
        return;
    }

    amountInput.value = Number(option.dataset.price).toFixed(2);
});

document.querySelector('[data-sale-date]')?.addEventListener('change', refreshSaleDatetimeLabel);
document.querySelector('[data-sale-time]')?.addEventListener('change', refreshSaleDatetimeLabel);
refreshSaleDatetimeLabel();

document.querySelector('[data-quote-date]')?.addEventListener('change', refreshQuoteDatetimeLabel);
document.querySelector('[data-quote-time]')?.addEventListener('change', refreshQuoteDatetimeLabel);
refreshQuoteDatetimeLabel();
refreshAllQuoteTotals();

if (document.querySelector('[data-quote-modal]:not(.hidden)')) {
    document.body.classList.add('overflow-hidden');
}

const closeSettingsMenus = () => {
    document.querySelectorAll('[data-settings-menu]').forEach((menu) => {
        menu.querySelector('[data-settings-menu-panel]')?.classList.add('hidden');
        menu.querySelector('[data-settings-menu-trigger]')?.setAttribute('aria-expanded', 'false');
    });
};

document.addEventListener('click', (event) => {
    const settingsTrigger = event.target.closest('[data-settings-menu-trigger]');

    if (settingsTrigger) {
        event.stopPropagation();
        const menu = settingsTrigger.closest('[data-settings-menu]');
        const panel = menu?.querySelector('[data-settings-menu-panel]');
        const isOpen = panel && ! panel.classList.contains('hidden');

        closeSettingsMenus();

        if (! isOpen && panel) {
            panel.classList.remove('hidden');
            settingsTrigger.setAttribute('aria-expanded', 'true');
        }

        return;
    }

    if (! event.target.closest('[data-settings-menu]')) {
        closeSettingsMenus();
    }

    const overlayOpenButton = event.target.closest('[data-overlay-open]');

    if (overlayOpenButton) {
        closeSettingsMenus();
        openOverlay(overlayOpenButton.dataset.overlayOpen);

        return;
    }

    const saleCloseButton = event.target.closest('[data-sale-close]');

    if (saleCloseButton) {
        closeOverlay(saleCloseButton.closest('[data-sale-modal]'));

        return;
    }

    const appointmentCloseButton = event.target.closest('[data-appointment-close]');

    if (appointmentCloseButton) {
        closeOverlay(appointmentCloseButton.closest('[data-appointment-modal]'));

        return;
    }

    const quoteCloseButton = event.target.closest('[data-quote-close]');

    if (quoteCloseButton) {
        closeOverlay(quoteCloseButton.closest('[data-quote-modal]'));

        return;
    }

    const quoteServiceAddButton = event.target.closest('[data-quote-service-add]');

    if (quoteServiceAddButton) {
        const editor = quoteServiceAddButton.closest('[data-quote-service-editor]');
        const list = editor?.querySelector('[data-quote-service-list]');
        const template = editor?.querySelector('[data-quote-service-template]');
        const index = Date.now();

        list?.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
        reindexQuoteServiceRows(editor);
        refreshQuoteTotalForEditor(editor);

        return;
    }

    const quoteServiceRemoveButton = event.target.closest('[data-quote-service-remove]');

    if (quoteServiceRemoveButton) {
        const editor = quoteServiceRemoveButton.closest('[data-quote-service-editor]');
        const rows = editor?.querySelectorAll('[data-quote-service-row]') ?? [];

        if (rows.length <= 1) {
            return;
        }

        quoteServiceRemoveButton.closest('[data-quote-service-row]')?.remove();
        reindexQuoteServiceRows(editor);
        refreshQuoteTotalForEditor(editor);

        return;
    }

    const quoteServiceSelect = event.target.closest('[data-quote-service-select]');
    const quoteServiceQty = event.target.closest('[data-quote-service-qty]');

    if (quoteServiceSelect || quoteServiceQty) {
        refreshQuoteTotalForEditor((quoteServiceSelect || quoteServiceQty).closest('[data-quote-service-editor]'));
    }

    const openButton = event.target.closest('[data-modal-open]');
    const closeButton = event.target.closest('[data-modal-close]');
    const tabButton = event.target.closest('[data-tab-target]');
    const addVehicleButton = event.target.closest('[data-vehicle-add]');
    const removeVehicleButton = event.target.closest('[data-vehicle-remove]');

    if (openButton) {
        document.getElementById(openButton.dataset.modalOpen)?.showModal();
        refreshAllQuoteTotals();
    }

    if (closeButton) {
        closeButton.closest('dialog')?.close();
    }

    if (tabButton) {
        const dialog = tabButton.closest('dialog');

        dialog.querySelectorAll('[data-tab-target]').forEach((button) => {
            const isActive = button === tabButton;
            button.setAttribute('aria-selected', isActive.toString());
            button.classList.toggle('bg-yellow-300', isActive);
            button.classList.toggle('text-black', isActive);
            button.classList.toggle('text-zinc-300', !isActive);
        });

        dialog.querySelectorAll('[data-tab-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.id !== tabButton.dataset.tabTarget);
        });
    }

    if (addVehicleButton) {
        const editor = addVehicleButton.closest('[data-vehicle-editor]');
        const list = editor.querySelector('[data-vehicle-list]');
        const template = editor.querySelector('[data-vehicle-template]');
        const index = Date.now();

        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
    }

    if (removeVehicleButton) {
        removeVehicleButton.closest('[data-vehicle-row]')?.remove();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSettingsMenus();
        closeAllOverlays();
    }
});

document.addEventListener('click', async (event) => {
    const printButton = event.target.closest('[data-print-quote]');

    if (printButton) {
        window.print();

        return;
    }

    const copyButton = event.target.closest('[data-copy-share]');

    if (copyButton) {
        const url = copyButton.dataset.copyShare;
        const label = copyButton.querySelector('[data-copy-label]');
        const original = label?.textContent;

        try {
            await navigator.clipboard.writeText(url);
        } catch (error) {
            const field = document.querySelector('[data-share-url]');
            field?.focus();
            field?.select();
            document.execCommand?.('copy');
        }

        if (label) {
            label.textContent = 'Link copiado!';
            window.setTimeout(() => {
                label.textContent = original;
            }, 2000);
        }
    }
});

window.openOverlay = openOverlay;
