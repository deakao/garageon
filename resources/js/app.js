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
    modal.querySelectorAll('[data-quote-service-editor]').forEach(refreshQuoteTotalForEditor);

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

    const hasOpenOverlay = Object.values(overlaySelectors).some((selector) => document.querySelector(`${selector}:not(.hidden)`));

    if (! hasOpenOverlay) {
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

const formatPhoneInput = (input) => {
    const digits = input.value.replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 2) {
        input.value = digits ? `(${digits}` : '';

        return;
    }

    if (digits.length <= 6) {
        input.value = `(${digits.slice(0, 2)}) ${digits.slice(2)}`;

        return;
    }

    const prefixLength = digits.length === 11 ? 7 : 6;
    input.value = `(${digits.slice(0, 2)}) ${digits.slice(2, prefixLength)}-${digits.slice(prefixLength)}`;
};

const refreshQuoteTotalForEditor = (editor) => {
    const totalEl = editor?.querySelector('[data-quote-total]');
    const amountInput = editor?.closest('form')?.querySelector('[data-sale-amount]');
    const loyaltyPreview = editor?.querySelector('[data-sale-loyalty-preview]');
    const durationPreview = editor?.querySelector('[data-appointment-duration-preview]');

    let total = 0;
    let loyaltyPoints = 0;
    let durationMinutes = 0;

    editor.querySelectorAll('[data-quote-service-row]').forEach((row) => {
        const select = row.querySelector('[data-quote-service-select]');
        const qtyInput = row.querySelector('[data-quote-service-qty]');
        const price = Number(select?.selectedOptions[0]?.dataset.price ?? 0);
        const points = Number(select?.selectedOptions[0]?.dataset.loyaltyPoints ?? 0);
        const duration = Number(select?.selectedOptions[0]?.dataset.durationMinutes ?? 0);
        const qty = Number(qtyInput?.value ?? 1);

        if (select?.value && qty > 0) {
            total += price * qty;
            loyaltyPoints += points * qty;
            durationMinutes += duration * qty;
        }
    });

    if (totalEl) {
        totalEl.textContent = formatQuoteMoney(total);
    }

    if (amountInput && editor.matches('[data-sale-service-editor]')) {
        amountInput.value = total.toFixed(2);
    }

    if (loyaltyPreview) {
        loyaltyPreview.textContent = total > 0
            ? `Esta venda soma ${loyaltyPoints.toLocaleString('pt-BR')} pts para o cliente.`
            : 'Escolha um serviço para ver os pontos da venda.';
    }

    if (durationPreview) {
        durationPreview.textContent = durationMinutes > 0
            ? `${durationMinutes.toLocaleString('pt-BR')} min`
            : '0 min';
    }
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
    const form = lookup.closest('form');
    const customerName = form?.querySelector('[data-customer-name]');
    const customerPhone = form?.querySelector('[data-customer-phone]');
    const loyaltyBalance = form?.querySelector('[data-sale-loyalty-balance]');
    const loyaltyDebit = form?.querySelector('[data-sale-loyalty-debit]');
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
            const points = Number(vehicle.customer_loyalty_points ?? 0);
            if (loyaltyBalance) {
                loyaltyBalance.textContent = `Saldo disponível: ${points.toLocaleString('pt-BR')} pts.`;
            }

            if (loyaltyDebit) {
                loyaltyDebit.max = points.toString();
            }

            setStatus(`Cliente e veículo encontrados. Saldo: ${points.toLocaleString('pt-BR')} pts.`, 'success');
        } else {
            if (loyaltyBalance) {
                loyaltyBalance.textContent = 'Saldo será validado ao salvar a venda.';
            }

            loyaltyDebit?.removeAttribute('max');
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
            { select: 5, type: 'date', format: 'DD/MM/YYYY', sortSequence: ['desc', 'asc'] },
            { select: 6, sortable: false },
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
            { select: 6, sortable: false },
        ],
    });
});

document.querySelectorAll('[data-quotes-kanban]').forEach((board) => {
    const csrfToken = board.dataset.csrf;
    const statusUrlTemplate = board.dataset.statusUrlTemplate;
    const feedback = board.querySelector('[data-quotes-kanban-feedback]');
    const searchInput = board.querySelector('[data-quotes-kanban-search]');
    const emptyFilter = board.querySelector('[data-quotes-kanban-empty-filter]');
    let draggedCard = null;
    let originList = null;

    const setFeedback = (message, type = 'success') => {
        if (! feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.remove('hidden', 'border-yellow-300/25', 'bg-yellow-300/10', 'text-yellow-100', 'border-red-300/25', 'bg-red-300/10', 'text-red-100');

        if (type === 'error') {
            feedback.classList.add('border-red-300/25', 'bg-red-300/10', 'text-red-100');
        } else {
            feedback.classList.add('border-yellow-300/25', 'bg-yellow-300/10', 'text-yellow-100');
        }
    };

    const refreshColumnMeta = (list) => {
        const column = list.closest('[data-quotes-column]');
        const count = list.querySelectorAll('[data-quote-card]:not(.hidden)').length;
        const emptyState = list.querySelector('[data-column-empty]');
        const countLabel = column?.querySelector('[data-column-count]');
        const countWord = column?.querySelector('[data-column-count-label]');
        const badge = column?.querySelector('[data-column-badge]');

        if (countLabel) {
            countLabel.textContent = String(count);
        }

        if (countWord) {
            countWord.textContent = count === 1 ? 'proposta' : 'propostas';
        }

        if (badge) {
            badge.textContent = String(count);
        }

        if (emptyState) {
            emptyState.classList.toggle('hidden', count > 0);
            emptyState.classList.toggle('flex', count === 0);
        }
    };

    const refreshAllColumns = () => {
        board.querySelectorAll('[data-quotes-list]').forEach(refreshColumnMeta);

        if (emptyFilter) {
            const visibleCards = board.querySelectorAll('[data-quote-card]:not(.hidden)').length;
            const hasCards = board.querySelectorAll('[data-quote-card]').length > 0;
            emptyFilter.classList.toggle('hidden', ! hasCards || visibleCards > 0);
        }
    };

    const applySearch = () => {
        const term = (searchInput?.value ?? '').trim().toLowerCase();

        board.querySelectorAll('[data-quote-card]').forEach((card) => {
            const haystack = card.dataset.search ?? '';
            card.classList.toggle('hidden', term !== '' && ! haystack.includes(term));
        });

        refreshAllColumns();
    };

    const moveCard = (card, list, beforeCard = null) => {
        const emptyState = list.querySelector('[data-column-empty]');

        if (beforeCard && beforeCard !== card) {
            list.insertBefore(card, beforeCard);
        } else if (emptyState) {
            list.insertBefore(card, emptyState);
        } else {
            list.appendChild(card);
        }

        card.dataset.status = list.dataset.status;
    };

    const persistStatus = async (card, status, fallbackList) => {
        const quoteId = card.dataset.quoteId;
        const url = statusUrlTemplate.replace('__QUOTE__', quoteId);

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status }),
            });

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                throw new Error(data.message ?? 'Não foi possível atualizar o status.');
            }

            const statusSelect = document.querySelector(`#quote-edit-${quoteId} select[name="status"]`);

            if (statusSelect) {
                statusSelect.value = status;
            }

            setFeedback(data.message ?? 'Status do orçamento atualizado.');
        } catch (error) {
            moveCard(card, fallbackList);
            refreshAllColumns();
            setFeedback(error.message || 'Não foi possível atualizar o status.', 'error');
        }
    };

    board.querySelectorAll('[data-quote-card]').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            if (event.target.closest('a, button, form, input, select, textarea')) {
                event.preventDefault();
                return;
            }

            draggedCard = card;
            originList = card.closest('[data-quotes-list]');
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.quoteId);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('is-dragging');
            board.querySelectorAll('[data-quotes-list]').forEach((list) => list.classList.remove('is-drop-target'));
            draggedCard = null;
            originList = null;
            refreshAllColumns();
        });
    });

    board.querySelectorAll('[data-quotes-list]').forEach((list) => {
        list.addEventListener('dragover', (event) => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            list.classList.add('is-drop-target');

            if (! draggedCard) {
                return;
            }

            const afterCard = [...list.querySelectorAll('[data-quote-card]:not(.is-dragging):not(.hidden)')]
                .find((card) => {
                    const rect = card.getBoundingClientRect();
                    return event.clientY < rect.top + rect.height / 2;
                });

            moveCard(draggedCard, list, afterCard ?? null);
        });

        list.addEventListener('dragleave', (event) => {
            if (! list.contains(event.relatedTarget)) {
                list.classList.remove('is-drop-target');
            }
        });

        list.addEventListener('drop', (event) => {
            event.preventDefault();
            list.classList.remove('is-drop-target');

            if (! draggedCard) {
                return;
            }

            const nextStatus = list.dataset.status;
            const previousStatus = originList?.dataset.status;
            const previousList = originList;

            moveCard(draggedCard, list);
            refreshAllColumns();

            if (nextStatus && nextStatus !== previousStatus && previousList) {
                persistStatus(draggedCard, nextStatus, previousList);
            }
        });
    });

    searchInput?.addEventListener('input', applySearch);
    refreshAllColumns();
});

document.querySelectorAll('[data-vehicle-lookup]').forEach(initVehicleLookup);

document.querySelectorAll('[data-currency-mask]').forEach((input) => {
    input.addEventListener('input', () => formatCurrencyInput(input));
});

document.querySelectorAll('[data-phone-mask]').forEach((input) => {
    formatPhoneInput(input);
    input.addEventListener('input', () => formatPhoneInput(input));
});

document.querySelectorAll('[data-hours-form]').forEach((form) => {
    form.addEventListener('input', (event) => {
        const source = event.target.closest('[data-hour-field]');
        const sourceDay = Number(source?.closest('[data-hour-day]')?.dataset.hourDay);

        if (! source || sourceDay < 1 || sourceDay > 5 || ! form.querySelector('[data-replicate-weekdays]')?.checked) {
            return;
        }

        form.querySelectorAll('[data-hour-day]').forEach((row) => {
            const day = Number(row.dataset.hourDay);
            const target = row.querySelector(`[data-hour-field="${source.dataset.hourField}"]`);

            if (day < 1 || day > 5 || ! target) {
                return;
            }

            if (source.type === 'checkbox') {
                target.checked = source.checked;
            } else {
                target.value = source.value;
            }
        });
    });
});

document.querySelector('[data-sale-date]')?.addEventListener('change', refreshSaleDatetimeLabel);
document.querySelector('[data-sale-time]')?.addEventListener('change', refreshSaleDatetimeLabel);
refreshSaleDatetimeLabel();

document.querySelector('[data-quote-date]')?.addEventListener('change', refreshQuoteDatetimeLabel);
document.querySelector('[data-quote-time]')?.addEventListener('change', refreshQuoteDatetimeLabel);
refreshQuoteDatetimeLabel();
refreshAllQuoteTotals();

document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-quote-service-select]');

    if (select) {
        refreshQuoteTotalForEditor(select.closest('[data-quote-service-editor]'));
    }
});

document.addEventListener('input', (event) => {
    const quantity = event.target.closest('[data-quote-service-qty]');

    if (quantity) {
        refreshQuoteTotalForEditor(quantity.closest('[data-quote-service-editor]'));
    }
});

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

const syncAutomationSubjectVisibility = (form) => {
    const channel = form.querySelector('[data-automation-channel]');
    const subjectField = form.querySelector('[data-automation-subject-field]');

    if (! channel || ! subjectField) {
        return;
    }

    subjectField.classList.toggle('hidden', channel.value !== 'email');
};

document.querySelectorAll('[data-automation-form]').forEach((form) => {
    syncAutomationSubjectVisibility(form);
    form.querySelector('[data-automation-channel]')?.addEventListener('change', () => syncAutomationSubjectVisibility(form));
});
