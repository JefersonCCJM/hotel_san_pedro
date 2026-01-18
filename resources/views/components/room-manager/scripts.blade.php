@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    // Definir función global ANTES del listener de Livewire para que esté disponible inmediatamente
    /**
     * Abre el modal especializado para registrar un pago (abono).
     * Usa payments como Single Source of Truth.
     * 
     * @param {number} reservationId ID de la reserva
     * @param {number} nightPrice Precio de la noche (opcional, para botón rápido)
     * @param {object} financialContext Contexto financiero opcional (totalAmount, paymentsTotal, balanceDue)
     */
    window.openRegisterPayment = function(reservationId, nightPrice = null, financialContext = null) {
        console.log('openRegisterPayment called', { reservationId, nightPrice, financialContext });
        if (!reservationId || reservationId === 0) {
            console.error('reservationId inválido:', reservationId);
            alert('Error: ID de reserva inválido');
            return;
        }
        
        // Si no se proporciona el contexto financiero, usar valores por defecto
        // El contexto debería venir siempre desde room-detail-modal
        if (!financialContext) {
            financialContext = {
                totalAmount: 0,
                paymentsTotal: 0,
                balanceDue: 0
            };
        }
        
        // Esperar a que Livewire esté inicializado si no lo está
        if (typeof Livewire === 'undefined' || !Livewire.all) {
            console.warn('Livewire no está inicializado, esperando...');
            document.addEventListener('livewire:init', () => {
                openPaymentModal(reservationId, nightPrice, financialContext);
            });
        } else {
            openPaymentModal(reservationId, nightPrice, financialContext);
        }
    };
    
    /**
     * Abre el modal de pago con los datos proporcionados
     */
    function openPaymentModal(reservationId, nightPrice, financialContext) {
        window.dispatchEvent(new CustomEvent('open-payment-modal', {
            detail: {
                title: 'Registrar Pago',
                reservationId: reservationId,
                nightPrice: nightPrice || 0,
                financialContext: financialContext || {
                    totalAmount: 0,
                    paymentsTotal: 0,
                    balanceDue: 0
                }
            }
        }));
    }

    document.addEventListener('livewire:init', () => {
        let customerSelect = null;
        let additionalGuestSelect = null;
        let productSelect = null;
        
        // Escuchar evento personalizado para registrar pagos
        // Usar Livewire.on para escuchar el evento directamente
        Livewire.on('register-payment-event', (data) => {
            const paymentData = Array.isArray(data) ? data[0] : data;
            if (!paymentData) {
                console.error('[Payment Handler] No se recibieron datos de pago');
                window.dispatchEvent(new CustomEvent('reset-payment-modal-loading'));
                return;
            }
            
            console.log('[Payment Handler] Livewire event received:', paymentData);
            // El método handleRegisterPayment se llamará automáticamente
        });
        
        // También escuchar el evento DOM para compatibilidad
        window.addEventListener('register-payment-event', (event) => {
            const paymentData = event.detail;
            if (!paymentData) {
                console.error('[Payment Handler] No se recibieron datos de pago (DOM event)');
                window.dispatchEvent(new CustomEvent('reset-payment-modal-loading'));
                return;
            }
            
            console.log('[Payment Handler] DOM event received, dispatching Livewire event:', paymentData);
            
            // Disparar evento de Livewire que será capturado por el listener #[On('register-payment')]
            Livewire.dispatch('register-payment', [
                paymentData.reservationId,
                paymentData.amount,
                paymentData.paymentMethod,
                paymentData.bankName || null,
                paymentData.reference || null
            ]);
        });


        // Toast notifications are handled by x-notifications.toast component
        Livewire.on('notify', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            console.log('Notify event:', payload);
    // The toast component listens to @notify.window
    window.dispatchEvent(new CustomEvent('notify', { detail: payload }));
});

// ===== ASIGNAR HUÉSPEDES (Completar Reserva Activa) =====
let assignCustomerSelect = null;
let assignAdditionalGuestSelect = null;

// Inicializar selector de cliente principal cuando se abre el modal
Livewire.on('assignGuestsModalOpened', () => {
    setTimeout(() => {
        if (assignCustomerSelect) assignCustomerSelect.destroy();

        const currentClientId = @this.get('assignGuestsForm.client_id') || null;

        assignCustomerSelect = new TomSelect('#assign_guests_customer_id', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'identification', 'text'],
            loadThrottle: 400,
            placeholder: 'Buscar cliente...',
            preload: true,
            load: function(query, callback) {
                fetch(`/api/customers/search?q=${encodeURIComponent(query || '')}`)
                    .then(response => response.json())
                    .then(data => {
                        const results = data.results || [];
                        callback(results);
                    })
                    .catch(() => callback());
            },
            onChange: function(value) {
                // 🔐 NORMALIZAR: convertir cadena vacía a null (requisito de BD INTEGER)
                const normalizedValue = (value === '' || value === null || value === undefined) 
                    ? null 
                    : (isNaN(value) ? null : parseInt(value));
                
                // Actualizar el valor en Livewire usando la notación de punto para arrays
                // Forzar reactividad actualizando la referencia completa del array si es necesario
                @this.set('assignGuestsForm.client_id', normalizedValue);
                
                // Debug: Log para verificar que se está actualizando
                console.log('[Assign Guests] Client ID changed:', normalizedValue);
            },
            render: {
                option: function(item, escape) {
                    const name = escape(item.name || item.text || '');
                    const id = escape(item.identification || '');
                    return `<div class="px-4 py-2 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                        <div class="font-bold text-gray-900">${name}</div>
                        ${id ? `<div class="text-[10px] text-gray-500 mt-0.5">ID: ${escape(id)}</div>` : ''}
                    </div>`;
                },
                item: function(item, escape) {
                    return `<div class="font-bold text-blue-700">${escape(item.name || item.text || '')}</div>`;
                }
            }
        });

        // Establecer valor inicial si existe
        if (currentClientId) {
            assignCustomerSelect.setValue(currentClientId, true);
        }
    }, 150);
});

// Inicializar selector de huéspedes adicionales
document.addEventListener('init-assign-additional-guest-select', function() {
    setTimeout(() => {
        if (assignAdditionalGuestSelect) {
            assignAdditionalGuestSelect.destroy();
        }

        assignAdditionalGuestSelect = new TomSelect('#assign_additional_guest_customer_id', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'identification', 'text'],
            loadThrottle: 400,
            placeholder: 'Buscar cliente...',
            preload: true,
            load: function(query, callback) {
                fetch(`/api/customers/search?q=${encodeURIComponent(query || '')}`)
                    .then(response => response.json())
                    .then(data => {
                        const results = data.results || [];
                        callback(results);
                    })
                    .catch(() => callback());
            },
            onChange: function(value) {
                if (value) {
                    @this.call('addAssignGuest', parseInt(value)).then(() => {
                        assignAdditionalGuestSelect.clear();
                    });
                }
            },
            render: {
                option: function(item, escape) {
                    const name = escape(item.name || item.text || '');
                    const id = escape(item.identification || '');
                    return `<div class="px-4 py-2 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                        <div class="font-bold text-gray-900">${name}</div>
                        ${id ? `<div class="text-[10px] text-gray-500 mt-0.5">ID: ${escape(id)}</div>` : ''}
                    </div>`;
                },
                item: function(item, escape) {
                    return `<div class="font-bold text-blue-700">${escape(item.name || item.text || '')}</div>`;
                }
            }
        });
    }, 100);
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
                
                // Inicializar TomSelect
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
                                
                                // Solo manejar el mensaje si es una búsqueda inicial (sin query)
                                if (!query || query.trim() === '') {
                                    const noCustomersMsg = document.getElementById('no-customers-message');
                                    if (noCustomersMsg) {
                                        if (results.length === 0) {
                                            // No hay clientes: MOSTRAR el mensaje
                                            console.log('No hay clientes, mostrando mensaje');
                                            noCustomersMsg.classList.remove('hidden');
                                        } else {
                                            // Hay clientes: OCULTAR el mensaje
                                            console.log('Hay clientes, ocultando mensaje');
                                            noCustomersMsg.classList.add('hidden');
                                        }
                                    }
                                }
                                
                                callback(results);
                            })
                            .catch(() => {
                                // En caso de error, ocultar el mensaje
                                const noCustomersMsg = document.getElementById('no-customers-message');
                                if (noCustomersMsg) {
                                    noCustomersMsg.classList.add('hidden');
                                }
                                callback();
                            });
                    },
                    onChange: (val) => { 
                        // 🔐 NORMALIZAR: convertir cadena vacía a null (requisito de BD INTEGER)
                        // Livewire ejecutará automáticamente updatedRentFormClientId() cuando usamos @this.set()
                        const normalizedValue = (val === '' || val === null || val === undefined) ? null : (isNaN(val) ? null : parseInt(val));
                        @this.set('rentForm.client_id', normalizedValue);
                        // El hook updatedRentFormClientId() se ejecutará automáticamente y normalizará el valor + recalculará totales
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
            }, 150);  // Aumenté el timeout a 150ms
        });

        // Listen for customer created event from the new modal
        Livewire.on('customer-created', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            const customerId = payload?.customerId || payload?.customer?.id;
            const customerData = payload?.customer;
            const context = payload?.context || 'principal';
            
            console.log('Cliente creado - Contexto:', context, 'ID:', customerId);
            
            // SIEMPRE ocultar el mensaje cuando se crea un cliente
            const noCustomersMsg = document.getElementById('no-customers-message');
            if (noCustomersMsg) {
                noCustomersMsg.classList.add('hidden');
            }
            
            // ===== MANEJAR QUICK-RENT-MODAL (Cliente Principal) =====
            if (customerSelect && customerId) {
                // Agregar el nuevo cliente a las opciones
                if (customerData) {
                    customerSelect.addOption({
                        id: customerData.id,
                        name: customerData.name,
                        identification: customerData.identification,
                        text: customerData.name
                    });
                }
                
                // Solo seleccionar como principal si el contexto es 'principal'
                if (context === 'principal') {
                    console.log('Asignando cliente como PRINCIPAL en Quick Rent');
                    customerSelect.setValue(customerId);
                    // Actualizar también Livewire (normalizar antes de set)
                    // El hook updatedRentFormClientId() se ejecutará automáticamente
                    const normalizedId = customerId ? parseInt(customerId) : null;
                    @this.set('rentForm.client_id', normalizedId);
                } else {
                    console.log('Cliente creado en contexto ADICIONAL en Quick Rent, agregando a lista de huéspedes');
                    // Agregar automáticamente como huésped adicional
                    if (customerId) {
                        @this.call('addGuestFromCustomerId', customerId);
                    }
                }
            }

            // ===== MANEJAR ASSIGN-GUESTS-MODAL (Completar Reserva) =====
            // Si el modal de asignar huéspedes está abierto y el contexto es 'principal'
            if (assignCustomerSelect && customerId && context === 'principal') {
                console.log('Asignando cliente como PRINCIPAL en Assign Guests Modal');
                
                // Agregar el nuevo cliente a las opciones
                if (customerData) {
                    assignCustomerSelect.addOption({
                        id: customerData.id,
                        name: customerData.name,
                        identification: customerData.identification,
                        text: customerData.name
                    });
                }
                
                // Seleccionar automáticamente como cliente principal
                assignCustomerSelect.setValue(customerId);
                
                // Actualizar Livewire (normalizar antes de set)
                const normalizedId = customerId ? parseInt(customerId) : null;
                @this.set('assignGuestsForm.client_id', normalizedId);
                
                console.log('[Assign Guests] Cliente principal asignado automáticamente:', normalizedId);
            }

            // ===== MANEJAR ASSIGN-GUESTS-MODAL (Huéspedes Adicionales) =====
            // Si el contexto es 'additional' y el selector de huéspedes adicionales está inicializado
            if (assignAdditionalGuestSelect && customerId && context === 'additional') {
                console.log('Cliente creado en contexto ADICIONAL en Assign Guests Modal, agregando automáticamente');
                
                // Agregar el nuevo cliente a las opciones
                if (customerData) {
                    assignAdditionalGuestSelect.addOption({
                        id: customerData.id,
                        name: customerData.name,
                        identification: customerData.identification,
                        text: customerData.name
                    });
                }
                
                // Agregar automáticamente como huésped adicional
                @this.call('addAssignGuest', parseInt(customerId)).then(() => {
                    assignAdditionalGuestSelect.clear();
                });
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
                            const mainCustomerId = @this.get('rentForm.client_id');
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

    function confirmRelease(roomId, roomNumber, totalDebt, reservationId, isCancellation = false) {
        // Load room release data and show confirmation modal
        @this.call('loadRoomReleaseData', roomId, isCancellation).then((data) => {
            // Add flag to indicate if this is a cancellation action
            if (isCancellation) {
                data.is_cancellation = true;
            }
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

    // Función confirmRevertNight eliminada - Los pagos se gestionan a través de la tabla payments;

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

