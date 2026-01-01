{{-- Room Manager Scripts Component --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            let productSelect = null;

            // Toast notifications are handled by x-notifications.toast component
            Livewire.on('notify', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                window.dispatchEvent(new CustomEvent('notify', { detail: payload }));
            });

            Livewire.on('initAddSaleSelect', () => {
                setTimeout(() => {
                    if (productSelect) productSelect.destroy();
                    productSelect = new TomSelect('#detail_product_id', {
                        valueField: 'id',
                        labelField: 'name',
                        searchField: ['name', 'sku'],
                        loadThrottle: 400,
                        placeholder: 'Buscar...',
                        preload: true,
                        load: (query, callback) => {
                            fetch(`/api/products/search?q=${encodeURIComponent(query)}`)
                                .then(r => r.json()).then(j => callback(j.results))
                                .catch(() => callback());
                        },
                        onChange: (val) => {
                            @this.set('newSale.product_id', val);
                        },
                        render: {
                            option: (i, e) => `
                            <div class="px-4 py-2 border-b border-gray-50 flex justify-between items-center hover:bg-blue-50 transition-colors">
                                <div>
                                    <div class="font-bold text-gray-900">${e(i.name)}</div>
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wider">SKU: ${e(i.sku)} | Stock: ${e(i.quantity || i.stock)}</div>
                                </div>
                                <div class="text-blue-600 font-bold">${new Intl.NumberFormat('es-CO').format(i.price)}</div>
                            </div>`,
                            item: (i, e) =>
                                `<div class="font-bold text-blue-700">${e(i.name)}</div>`
                        }
                    });
                }, 100);
            });
        });

        function confirmRelease(roomId, roomNumber, totalDebt, reservationId) {
            const hasDebt = totalDebt && totalDebt > 0;
            const validReservationId = reservationId && reservationId !== 'null' ? reservationId : null;

            if (hasDebt && validReservationId) {
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        title: '¡Habitación con Deuda!',
                        html: `La habitación #${roomNumber} tiene una deuda pendiente de <b>${new Intl.NumberFormat('es-CO', {style:'currency', currency:'COP', minimumFractionDigits:0}).format(totalDebt)}</b>.<br><br>¿Desea marcar todo como pagado antes de liberar?`,
                        icon: 'warning',
                        confirmText: 'Pagar Todo y Continuar',
                        cancelText: 'Cancelar',
                        confirmButtonClass: 'bg-emerald-600 hover:bg-emerald-700',
                        onConfirm: () => {
                            window.dispatchEvent(new CustomEvent('open-select-modal', {
                                detail: {
                                    title: 'Método de Pago',
                                    text: '¿Cómo se salda la deuda total?',
                                    options: [
                                        { label: 'Efectivo', value: 'efectivo', class: 'bg-emerald-600 hover:bg-emerald-700' },
                                        { label: 'Transferencia', value: 'transferencia', class: 'bg-blue-600 hover:bg-blue-700' }
                                    ],
                                    onSelect: (method) => {
                                        @this.payEverything(validReservationId, method).then(() => {
                                            showReleaseOptions(roomId, roomNumber);
                                        });
                                    }
                                }
                            }));
                        },
                        onCancel: () => {
                            showReleaseOptions(roomId, roomNumber);
                        }
                    }
                }));
            } else {
                showReleaseOptions(roomId, roomNumber);
            }
        }

        function showReleaseOptions(roomId, roomNumber) {
            const livewireComponent = @this;

            window.dispatchEvent(new CustomEvent('open-select-modal', {
                detail: {
                    title: 'Liberar Habitación #' + roomNumber,
                    text: '¿En qué estado desea dejar la habitación?',
                    options: [
                        { label: 'Libre', value: 'libre', class: 'bg-emerald-500 hover:bg-emerald-600' },
                        { label: 'Pendiente por Aseo', value: 'pendiente_aseo', class: 'bg-amber-500 hover:bg-amber-600' },
                        { label: 'Limpia', value: 'limpia', class: 'bg-blue-500 hover:bg-blue-600' }
                    ],
                    onSelect: (action) => {
                        livewireComponent.call('releaseRoom', roomId, action);
                    }
                }
            }));
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

        function addDeposit(reservationId) {
            window.dispatchEvent(new CustomEvent('open-input-modal', {
                detail: {
                    title: 'Agregar Abono',
                    fields: [
                        {
                            name: 'amount',
                            label: 'Monto',
                            type: 'number',
                            value: 0,
                            placeholder: '0.00',
                            min: 0,
                            step: 0.01
                        },
                        {
                            name: 'payment_method',
                            label: 'Método de Pago',
                            type: 'select',
                            value: 'efectivo',
                            options: [
                                { value: 'efectivo', label: 'Efectivo' },
                                { value: 'transferencia', label: 'Transferencia' }
                            ]
                        },
                        {
                            name: 'notes',
                            label: 'Notas (Opcional)',
                            type: 'textarea',
                            value: '',
                            placeholder: 'Notas adicionales...'
                        }
                    ],
                    confirmText: 'Registrar Abono',
                    confirmButtonClass: 'bg-emerald-600 hover:bg-emerald-700',
                    validator: (fields) => {
                        const amount = parseFloat(fields[0]?.value || 0);
                        if (!amount || amount <= 0) {
                            return { valid: false, message: 'El monto debe ser mayor a 0' };
                        }
                        return { valid: true };
                    },
                    onConfirm: (values) => {
                        const amount = parseFloat(values[0]?.value || 0);
                        const paymentMethod = values[1]?.value || 'efectivo';
                        const notes = values[2]?.value || null;
                        @this.addDeposit(reservationId, amount, paymentMethod, notes);
                    }
                }
            }));
        }

        function editDeposit(reservationId, current) {
            window.dispatchEvent(new CustomEvent('open-input-modal', {
                detail: {
                    title: 'Modificar Abono Total',
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

        function editDepositRecord(depositId, currentAmount, currentMethod, currentNotes) {
            window.dispatchEvent(new CustomEvent('open-input-modal', {
                detail: {
                    title: 'Editar Abono',
                    fields: [
                        {
                            name: 'amount',
                            label: 'Monto',
                            type: 'number',
                            value: currentAmount || 0,
                            placeholder: '0.00',
                            min: 0,
                            step: 0.01
                        },
                        {
                            name: 'payment_method',
                            label: 'Método de Pago',
                            type: 'select',
                            value: currentMethod || 'efectivo',
                            options: [
                                { value: 'efectivo', label: 'Efectivo' },
                                { value: 'transferencia', label: 'Transferencia' }
                            ]
                        },
                        {
                            name: 'notes',
                            label: 'Notas (Opcional)',
                            type: 'textarea',
                            value: currentNotes || '',
                            placeholder: 'Notas adicionales...'
                        }
                    ],
                    confirmText: 'Actualizar',
                    confirmButtonClass: 'bg-emerald-600 hover:bg-emerald-700',
                    validator: (fields) => {
                        const amount = parseFloat(fields[0]?.value || 0);
                        if (!amount || amount <= 0) {
                            return { valid: false, message: 'El monto debe ser mayor a 0' };
                        }
                        return { valid: true };
                    },
                    onConfirm: (values) => {
                        const amount = parseFloat(values[0]?.value || 0);
                        const paymentMethod = values[1]?.value || 'efectivo';
                        const notes = values[2]?.value || null;
                        @this.editDepositRecord(depositId, amount, paymentMethod, notes);
                    }
                }
            }));
        }

        function confirmDeleteDeposit(depositId) {
            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: {
                    title: 'Eliminar Abono',
                    text: '¿Está seguro de que desea eliminar este abono? Esta acción no se puede deshacer.',
                    icon: 'warning',
                    confirmText: 'Sí, eliminar',
                    confirmButtonClass: 'bg-red-600 hover:bg-red-700',
                    onConfirm: () => {
                        @this.deleteDepositRecord(depositId);
                    }
                }
            }));
        }
    </script>
@endpush

