@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        let customerSelect = null;
        let additionalGuestSelect = null;
        let productSelect = null;

        // Toast notifications are handled by x-notifications.toast component
        Livewire.on('notify', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            console.log('Notify event:', payload);
            // The toast component listens to @notify.window
            window.dispatchEvent(new CustomEvent('notify', { detail: payload }));
        });

        // Debug: escuchar errores de validación
        Livewire.on('validation-errors', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            console.error('Validation errors:', payload);
        });

        Livewire.on('initAddSaleSelect', () => {
            setTimeout(() => {
                if (productSelect) productSelect.destroy();
                productSelect = new TomSelect('#detail_product_id', {
                    valueField: 'id', labelField: 'name', searchField: ['name', 'sku'], loadThrottle: 400, placeholder: 'Buscar...',
                    preload: true,
                    load: (query, callback) => {
                        fetch(`/api/products/search?q=${encodeURIComponent(query)}`).then(r => r.json()).then(j => callback(j.results)).catch(() => callback());
                    },
                    onChange: (val) => { @this.set('newSale.product_id', val); },
                    render: {
                        option: (i, e) => `
                            <div class="px-4 py-2 border-b border-gray-50 flex justify-between items-center hover:bg-blue-50 transition-colors">
                                <div>
                                    <div class="font-bold text-gray-900">${e(i.name)}</div>
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wider">SKU: ${e(i.sku)} | Stock: ${e(i.quantity || i.stock)}</div>
                                </div>
                                <div class="text-blue-600 font-bold">${new Intl.NumberFormat('es-CO').format(i.price)}</div>
                            </div>`,
                        item: (i, e) => `<div class="font-bold text-blue-700">${e(i.name)}</div>`
                    }
                });
            }, 100);
        });

        Livewire.on('quickRentOpened', () => {
            setTimeout(() => {
                if (customerSelect) customerSelect.destroy();
                customerSelect = new TomSelect('#quick_customer_id', {
                    valueField: 'id', 
                    labelField: 'name', 
                    searchField: ['name', 'identification', 'text'], 
                    loadThrottle: 400, 
                    placeholder: 'Buscar cliente...',
                    preload: true,
                    load: (query, callback) => {
                        fetch(`/api/customers/search?q=${encodeURIComponent(query || '')}`)
                            .then(r => r.json())
                            .then(j => {
                                const results = j.results || [];
                                callback(results);
                            })
                            .catch(() => callback());
                    },
                    onChange: (val) => { 
                        @this.set('rentForm.customer_id', val || '');
                    },
                    render: {
                        option: (item, escape) => {
                            const name = escape(item.name || item.text || '');
                            const id = escape(item.identification || '');
                            return `<div class="px-4 py-2 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                                <div class="font-bold text-gray-900">${name}</div>
                                ${id ? `<div class="text-[10px] text-gray-500 mt-0.5">ID: ${escape(id)}</div>` : ''}
                            </div>`;
                        },
                        item: (item, escape) => {
                            return `<div class="font-bold text-blue-700">${escape(item.name || item.text || '')}</div>`;
                        },
                        no_results: () => {
                            return '<div class="px-4 py-2 text-gray-500 text-sm">No se encontraron clientes</div>';
                        }
                    }
                });
            }, 100);
        });

        // Listen for customer created event from the new modal
        // Solo actualizar el select si no hay un cliente seleccionado actualmente
        Livewire.on('customer-created', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            const customerId = payload?.customerId || payload?.customer?.id;
            
            if (customerSelect && customerId) {
                const currentValue = customerSelect.getValue();
                
                // Solo actualizar si no hay un cliente seleccionado
                // Si ya hay uno seleccionado, solo recargar las opciones sin cambiar la selección
                if (!currentValue || currentValue.length === 0) {
                    // Reload options to include the new customer
                    customerSelect.load((query, callback) => {
                        fetch(`/api/customers/search?q=`)
                            .then(r => r.json())
                            .then(j => callback(j.results || []))
                            .catch(() => callback());
                    });
                    // Set the new customer as selected
                    setTimeout(() => {
                        customerSelect.setValue(customerId);
                    }, 200);
                } else {
                    // Si ya hay un cliente seleccionado, solo recargar las opciones sin cambiar
                    customerSelect.load((query, callback) => {
                        fetch(`/api/customers/search?q=`)
                            .then(r => r.json())
                            .then(j => callback(j.results || []))
                            .catch(() => callback());
                    });
                }
            }
        });

        // Legacy event listener for backward compatibility
        Livewire.on('customerCreated', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            const customerId = payload?.customerId || payload;
            if (customerSelect && customerId) {
                customerSelect.load((query, callback) => {
                    fetch(`/api/customers/search?q=`)
                        .then(r => r.json())
                        .then(j => callback(j.results || []))
                        .catch(() => callback());
                });
                setTimeout(() => {
                    customerSelect.setValue(customerId);
                }, 200);
            }
        });

        // Initialize additional guest selector
        function initAdditionalGuestSelect() {
            setTimeout(() => {
                if (additionalGuestSelect) additionalGuestSelect.destroy();
                const selectElement = document.getElementById('additional_guest_customer_id');
                if (!selectElement) return;
                
                additionalGuestSelect = new TomSelect('#additional_guest_customer_id', {
                    valueField: 'id', 
                    labelField: 'name', 
                    searchField: ['name', 'identification', 'text'], 
                    loadThrottle: 400, 
                    placeholder: 'Buscar cliente...',
                    preload: true,
                    load: (query, callback) => {
                        fetch(`/api/customers/search?q=${encodeURIComponent(query || '')}`)
                            .then(r => r.json())
                            .then(j => {
                                const results = j.results || [];
                                callback(results);
                            })
                            .catch(() => callback());
                    },
                    onChange: (val) => { 
                        if (val) {
                            const mainCustomerId = @this.get('rentForm.customer_id');
                            if (mainCustomerId && String(val) === String(mainCustomerId)) {
                                window.dispatchEvent(new CustomEvent('notify', {
                                    detail: {
                                        type: 'error',
                                        message: 'El huésped principal no puede agregarse como huésped adicional.'
                                    }
                                }));
                                if (additionalGuestSelect) {
                                    additionalGuestSelect.clear();
                                }
                                return;
                            }
                            @this.call('addGuestFromCustomerId', val);
                            // Clear the select after adding
                            setTimeout(() => {
                                if (additionalGuestSelect) {
                                    additionalGuestSelect.clear();
                                }
                            }, 100);
                        }
                    },
                    render: {
                        option: (item, escape) => {
                            const name = escape(item.name || item.text || '');
                            const id = escape(item.identification || '');
                            return `<div class="px-4 py-2 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                                <div class="font-bold text-gray-900">${name}</div>
                                ${id ? `<div class="text-[10px] text-gray-500 mt-0.5">ID: ${escape(id)}</div>` : ''}
                            </div>`;
                        },
                        item: (item, escape) => {
                            return `<div class="font-bold text-blue-700">${escape(item.name || item.text || '')}</div>`;
                        },
                        no_results: () => {
                            return '<div class="px-4 py-2 text-gray-500 text-sm">No se encontraron clientes</div>';
                        }
                    }
                });
            }, 100);
        }

        // Listen for initialization event from Alpine.js
        document.addEventListener('init-additional-guest-select', initAdditionalGuestSelect);

        // Listen for guest added event to refresh the select
        Livewire.on('guest-added', () => {
            if (additionalGuestSelect) {
                additionalGuestSelect.clear();
            }
        });
    });

    function confirmRelease(roomId, roomNumber, totalDebt, reservationId) {
        // Load room release data and show confirmation modal
        @this.call('loadRoomReleaseData', roomId).then((data) => {
            window.dispatchEvent(new CustomEvent('open-release-confirmation', {
                detail: data
            }));
        });
    }

    function confirmPaySale(saleId) {
        window.dispatchEvent(new CustomEvent('open-select-modal', {
            detail: {
                title: 'Registrar Pago de Consumo',
                options: [
                    { label: 'Efectivo', value: 'efectivo', class: 'bg-emerald-600 hover:bg-emerald-700' },
                    { label: 'Transferencia', value: 'transferencia', class: 'bg-blue-600 hover:bg-blue-700' }
                ],
                onSelect: (method) => {
                    @this.paySale(saleId, method);
                }
            }
        }));
    }

    function confirmRevertSale(saleId) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: 'Anular Pago de Consumo',
                text: '¿Está seguro de que desea anular el pago de este consumo?',
                icon: 'warning',
                confirmText: 'Sí, anular',
                confirmButtonClass: 'bg-red-600 hover:bg-red-700',
                onConfirm: () => {
                    @this.paySale(saleId, 'pendiente');
                }
            }
        }));
    }

    function confirmPayStay(reservationId, amount) {
        window.dispatchEvent(new CustomEvent('open-select-modal', {
            detail: {
                title: 'Pagar Noche de Hospedaje',
                text: '¿Cómo desea registrar el pago de esta noche?',
                options: [
                    { label: 'Efectivo', value: 'efectivo', class: 'bg-emerald-600 hover:bg-emerald-700' },
                    { label: 'Transferencia', value: 'transferencia', class: 'bg-blue-600 hover:bg-blue-700' }
                ],
                onSelect: (method) => {
                    @this.payNight(reservationId, amount, method);
                }
            }
        }));
    }

    function confirmRevertNight(reservationId, amount) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: 'Anular Pago de Noche',
                text: '¿Desea descontar el valor de esta noche del abono total?',
                icon: 'warning',
                confirmText: 'Sí, anular',
                confirmButtonClass: 'bg-red-600 hover:bg-red-700',
                onConfirm: () => {
                    @this.revertNightPayment(reservationId, amount);
                }
            }
        }));
    }

    function confirmDeleteDeposit(depositId, amount, formattedAmount) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: 'Eliminar Abono',
                html: `¿Está seguro de que desea eliminar este abono de <b class="text-red-600 font-bold">$${formattedAmount}</b>?`,
                warningText: 'Esta acción no se puede deshacer y se restará el monto del abono total de la reserva.',
                icon: 'error',
                isDestructive: true,
                confirmText: 'Sí, eliminar',
                cancelText: 'Cancelar',
                confirmButtonClass: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
                confirmIcon: 'fa-trash',
                onConfirm: () => {
                    @this.deleteDeposit(depositId, amount);
                }
            }
        }));
    }

    function confirmRefund(reservationId, amount, formattedAmount) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', {
            detail: {
                title: 'Registrar Devolución',
                html: `¿Desea registrar que se devolvió <b class="text-blue-600 font-bold">$${formattedAmount}</b> al cliente?`,
                warningText: 'Esta acción quedará registrada en el historial de auditoría.',
                icon: 'info',
                isDestructive: false,
                confirmText: 'Sí, registrar devolución',
                cancelText: 'Cancelar',
                confirmButtonClass: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
                confirmIcon: 'fa-check-circle',
                onConfirm: () => {
                    @this.registerCustomerRefund(reservationId);
                }
            }
        }));
    }

    function editDeposit(reservationId, current) {
        window.dispatchEvent(new CustomEvent('open-input-modal', {
            detail: {
                title: 'Modificar Abono',
                fields: [
                    {
                        name: 'amount',
                        label: 'Monto',
                        type: 'number',
                        value: current || 0,
                        placeholder: '0.00',
                        min: 0,
                        step: 0.01
                    }
                ],
                confirmText: 'Actualizar',
                confirmButtonClass: 'bg-emerald-600 hover:bg-emerald-700',
                validator: (fields) => {
                    const amount = parseFloat(fields[0]?.value || 0);
                    if (amount <= 0) {
                        return { valid: false, message: 'El monto debe ser mayor a 0' };
                    }
                    return { valid: true };
                },
                onConfirm: (values) => {
                    const amount = parseFloat(values[0]?.value || 0);
                    @this.updateDeposit(reservationId, amount);
                }
            }
        }));
    }
</script>
@endpush

