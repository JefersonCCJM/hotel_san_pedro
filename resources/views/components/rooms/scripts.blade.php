{{-- Room Manager Scripts Component --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            let productSelect = null;

            Livewire.on('notify', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    icon: payload.type || 'info',
                    title: payload.message || ''
                });
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
                Swal.fire({
                    title: '¡Habitación con Deuda!',
                    html: `La habitación #${roomNumber} tiene una deuda pendiente de <b>${new Intl.NumberFormat('es-CO', {style:'currency', currency:'COP', minimumFractionDigits:0}).format(totalDebt)}</b>.<br><br>¿Desea marcar todo como pagado antes de liberar?`,
                    icon: 'warning',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Pagar Todo y Continuar',
                    denyButtonText: 'Liberar con Deuda',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#10b981',
                    denyButtonColor: '#f59e0b',
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'rounded-xl',
                        denyButton: 'rounded-xl',
                        cancelButton: 'rounded-xl'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Método de Pago',
                            text: '¿Cómo se salda la deuda total?',
                            icon: 'question',
                            showDenyButton: true,
                            confirmButtonText: 'Efectivo',
                            denyButtonText: 'Transferencia',
                            confirmButtonColor: '#10b981',
                            denyButtonColor: '#3b82f6',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-xl',
                                denyButton: 'rounded-xl'
                            }
                        }).then((payResult) => {
                            if (payResult.isConfirmed || payResult.isDenied) {
                                const method = payResult.isConfirmed ? 'efectivo' : 'transferencia';
                                @this.payEverything(validReservationId, method).then(() => {
                                    showReleaseOptions(roomId, roomNumber);
                                });
                            }
                        });
                    } else if (result.isDenied) {
                        showReleaseOptions(roomId, roomNumber);
                    }
                });
            } else {
                showReleaseOptions(roomId, roomNumber);
            }
        }

        function showReleaseOptions(roomId, roomNumber) {
            const livewireComponent = @this;

            Swal.fire({
                title: 'Liberar Habitación #' + roomNumber,
                html: '<p class="text-gray-600 mb-6">¿En qué estado desea dejar la habitación?</p>' +
                    '<div class="flex flex-col gap-3 mt-4" id="swal-release-buttons">' +
                    '<button type="button" data-action="libre" class="swal-release-btn w-full py-3 px-6 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl transition-colors duration-200">Libre</button>' +
                    '<button type="button" data-action="pendiente_aseo" class="swal-release-btn w-full py-3 px-6 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-colors duration-200">Pendiente por Aseo</button>' +
                    '<button type="button" data-action="limpia" class="swal-release-btn w-full py-3 px-6 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl transition-colors duration-200">Limpia</button>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#6b7280',
                showConfirmButton: false,
                customClass: {
                    popup: 'rounded-2xl',
                    cancelButton: 'rounded-xl'
                },
                didOpen: () => {
                    setTimeout(() => {
                        const container = document.querySelector('#swal-release-buttons');
                        if (container) {
                            container.addEventListener('click', function(e) {
                                const btn = e.target.closest('.swal-release-btn');
                                if (btn) {
                                    const action = btn.getAttribute('data-action');
                                    Swal.close();
                                    livewireComponent.call('releaseRoom', roomId, action);
                                }
                            });
                        }
                    }, 50);
                }
            });
        }

        function confirmPaySale(saleId) {
            Swal.fire({
                title: 'Registrar Pago de Consumo',
                icon: 'question',
                showDenyButton: true,
                confirmButtonText: 'Efectivo',
                denyButtonText: 'Transferencia',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#3b82f6',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    denyButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    @this.paySale(saleId, result.isConfirmed ? 'efectivo' : 'transferencia');
                }
            });
        }

        function confirmRevertSale(saleId) {
            Swal.fire({
                title: 'Anular Pago de Consumo',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                confirmButtonColor: '#ef4444',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.paySale(saleId, 'pendiente');
                }
            });
        }

        function confirmPayStay(reservationId, amount) {
            Swal.fire({
                title: 'Pagar Noche de Hospedaje',
                text: '¿Cómo desea registrar el pago de esta noche?',
                icon: 'info',
                showDenyButton: true,
                confirmButtonText: 'Efectivo',
                denyButtonText: 'Transferencia',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#3b82f6',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    denyButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    const method = result.isConfirmed ? 'efectivo' : 'transferencia';
                    @this.payNight(reservationId, amount, method);
                }
            });
        }

        function confirmRevertNight(reservationId, amount) {
            Swal.fire({
                title: 'Anular Pago de Noche',
                text: "¿Desea descontar el valor de esta noche del abono total?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.revertNightPayment(reservationId, amount);
                }
            });
        }

        function addDeposit(reservationId) {
            Swal.fire({
                title: 'Agregar Abono',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Monto</label>
                            <input id="deposit_amount" type="number" min="0" step="0.01" class="swal2-input" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Método de Pago</label>
                            <select id="deposit_payment_method" class="swal2-input">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Notas (Opcional)</label>
                            <textarea id="deposit_notes" class="swal2-textarea" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Registrar Abono',
                confirmButtonColor: '#10b981',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                },
                preConfirm: () => {
                    const amount = parseFloat(document.getElementById('deposit_amount').value);
                    const paymentMethod = document.getElementById('deposit_payment_method').value;
                    const notes = document.getElementById('deposit_notes').value;

                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('El monto debe ser mayor a 0');
                        return false;
                    }

                    return {
                        amount: amount,
                        payment_method: paymentMethod,
                        notes: notes || null
                    };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    @this.addDeposit(
                        reservationId,
                        result.value.amount,
                        result.value.payment_method,
                        result.value.notes
                    );
                }
            });
        }

        function editDeposit(reservationId, current) {
            Swal.fire({
                title: 'Modificar Abono Total',
                input: 'number',
                inputValue: current,
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                confirmButtonColor: '#10b981',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.updateDeposit(reservationId, result.value);
                }
            });
        }

        function editDepositRecord(depositId, currentAmount, currentMethod, currentNotes) {
            Swal.fire({
                title: 'Editar Abono',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Monto</label>
                            <input id="edit_deposit_amount" type="number" min="0" step="0.01" class="swal2-input" value="${currentAmount}" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Método de Pago</label>
                            <select id="edit_deposit_payment_method" class="swal2-input">
                                <option value="efectivo" ${currentMethod === 'efectivo' ? 'selected' : ''}>Efectivo</option>
                                <option value="transferencia" ${currentMethod === 'transferencia' ? 'selected' : ''}>Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Notas (Opcional)</label>
                            <textarea id="edit_deposit_notes" class="swal2-textarea" placeholder="Notas adicionales...">${currentNotes || ''}</textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                confirmButtonColor: '#10b981',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                },
                preConfirm: () => {
                    const amount = parseFloat(document.getElementById('edit_deposit_amount').value);
                    const paymentMethod = document.getElementById('edit_deposit_payment_method').value;
                    const notes = document.getElementById('edit_deposit_notes').value;

                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('El monto debe ser mayor a 0');
                        return false;
                    }

                    return {
                        amount: amount,
                        payment_method: paymentMethod,
                        notes: notes || null
                    };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    @this.editDepositRecord(
                        depositId,
                        result.value.amount,
                        result.value.payment_method,
                        result.value.notes
                    );
                }
            });
        }

        function confirmDeleteDeposit(depositId) {
            Swal.fire({
                title: 'Eliminar Abono',
                text: '¿Está seguro de que desea eliminar este abono? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.deleteDepositRecord(depositId);
                }
            });
        }
    </script>
@endpush

