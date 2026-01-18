{{-- Payment Modal Component - Especializado para registrar pagos/abonos --}}
<div
    x-data="paymentModal()"
    x-show="isOpen"
    x-cloak
    @open-payment-modal.window="open($event.detail)"
    class="fixed inset-0 z-[9998] overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;"
    wire:ignore>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Backdrop --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cancel()"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal --}}
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            
            {{-- Header --}}
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-white text-lg"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white" id="modal-title" x-text="title"></h3>
                    </div>
                    <button @click="cancel()" class="text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white px-6 py-6">
                {{-- Contexto Financiero --}}
                <div class="mb-6 p-5 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Resumen Financiero</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Total Hospedaje</p>
                            <p class="text-lg font-black text-gray-900" x-text="formatCurrency(financialContext.totalAmount)"></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-emerald-600 uppercase mb-1">Abonos Realizados</p>
                            <p class="text-lg font-black text-emerald-700" x-text="formatCurrency(financialContext.paymentsTotal)"></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-red-600 uppercase mb-1">Saldo Pendiente</p>
                            <p class="text-lg font-black text-red-700" x-text="formatCurrency(financialContext.balanceDue)"></p>
                        </div>
                    </div>
                </div>

                {{-- Monto de Pago --}}
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Monto a Pagar <span class="text-red-500">*</span>
                    </label>
                    
                    {{-- Botones Rápidos --}}
                    <div class="flex flex-wrap gap-2 mb-3">
                        <button
                            type="button"
                            @click="setAmount(financialContext.balanceDue)"
                            :disabled="financialContext.balanceDue <= 0"
                            class="px-4 py-2 text-xs font-bold rounded-lg border-2 transition-all"
                            :class="paymentAmount === financialContext.balanceDue 
                                ? 'bg-emerald-600 text-white border-emerald-600' 
                                : 'bg-white text-emerald-600 border-emerald-300 hover:bg-emerald-50'"
                            :title="'Pagar todo el saldo: ' + formatCurrency(financialContext.balanceDue)">
                            <i class="fas fa-check-circle mr-1"></i> Pagar todo
                        </button>
                        <button
                            type="button"
                            @click="setAmount(nightPrice)"
                            :disabled="!nightPrice || nightPrice <= 0"
                            class="px-4 py-2 text-xs font-bold rounded-lg border-2 transition-all"
                            :class="paymentAmount === nightPrice 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-blue-600 border-blue-300 hover:bg-blue-50'"
                            :title="'Monto de la noche: ' + formatCurrency(nightPrice)">
                            <i class="fas fa-moon mr-1"></i> Monto de la noche
                        </button>
                        <button
                            type="button"
                            @click="setCustomAmount()"
                            class="px-4 py-2 text-xs font-bold rounded-lg border-2 transition-all"
                            :class="isCustomAmount 
                                ? 'bg-gray-600 text-white border-gray-600' 
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                            <i class="fas fa-edit mr-1"></i> Personalizado
                        </button>
                    </div>

                    {{-- Input de Monto --}}
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-bold">$</span>
                        <input
                            type="text"
                            x-model="paymentAmountDisplay"
                            @input="handleAmountInput($event)"
                            @blur="validateAmount()"
                            placeholder="0.00"
                            class="w-full pl-8 pr-4 py-3 text-lg font-bold border-2 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                            :class="amountError ? 'border-red-300 bg-red-50' : 'border-gray-200'">
                    </div>
                    <p x-show="amountError" x-text="amountError" class="mt-2 text-xs text-red-600 font-semibold"></p>
                    <p x-show="!amountError && paymentAmount > 0" class="mt-2 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span x-show="paymentAmount < financialContext.balanceDue">
                            Se registrará como abono. Saldo restante: <span class="font-bold text-red-600" x-text="formatCurrency(financialContext.balanceDue - paymentAmount)"></span>
                        </span>
                        <span x-show="paymentAmount >= financialContext.balanceDue && financialContext.balanceDue > 0">
                            Se saldará la cuenta completa.
                        </span>
                    </p>
                </div>

                {{-- Método de Pago --}}
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <select
                        x-model="paymentMethod"
                        @change="handlePaymentMethodChange()"
                        class="w-full px-4 py-3 text-sm font-semibold border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>

                {{-- Información de Efectivo --}}
                <div x-show="paymentMethod === 'efectivo'" 
                     x-transition
                     class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5"></i>
                        <p class="text-xs font-semibold text-amber-800">
                            <strong>Importante:</strong> Verifique el efectivo recibido antes de confirmar el pago.
                        </p>
                    </div>
                </div>

                {{-- Campos de Transferencia --}}
                <div x-show="paymentMethod === 'transferencia'" 
                     x-transition
                     class="mb-6 space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <h5 class="text-xs font-bold text-blue-800 uppercase mb-3">Datos de Transferencia</h5>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Banco <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="bankName"
                                    @blur="validateTransferFields()"
                                    placeholder="Ej: Banco de Bogotá, Bancolombia..."
                                    class="w-full px-4 py-3 text-sm border-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    :class="bankError ? 'border-red-300 bg-red-50' : 'border-gray-200'">
                                <p x-show="bankError" x-text="bankError" class="mt-1 text-xs text-red-600"></p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">
                                    Referencia / Comprobante <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="reference"
                                    @blur="validateTransferFields()"
                                    placeholder="Número de referencia o comprobante"
                                    class="w-full px-4 py-3 text-sm border-2 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                    :class="referenceError ? 'border-red-300 bg-red-50' : 'border-gray-200'">
                                <p x-show="referenceError" x-text="referenceError" class="mt-1 text-xs text-red-600"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resumen de Confirmación --}}
                <div x-show="paymentAmount > 0 && !amountError" 
                     x-transition
                     class="mb-6 p-5 bg-gray-50 rounded-xl border-2 border-gray-200">
                    <h5 class="text-xs font-bold text-gray-700 uppercase mb-3">Resumen del Pago</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Monto a registrar:</span>
                            <span class="font-bold text-gray-900" x-text="formatCurrency(paymentAmount)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Método de pago:</span>
                            <span class="font-bold text-gray-900" x-text="paymentMethod === 'efectivo' ? 'Efectivo' : 'Transferencia'"></span>
                        </div>
                        <template x-if="paymentMethod === 'transferencia'">
                            <div class="space-y-1 pt-2 border-t border-gray-300">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Banco:</span>
                                    <span class="font-bold text-gray-900" x-text="bankName || 'No especificado'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Referencia:</span>
                                    <span class="font-bold text-gray-900" x-text="reference || 'No especificado'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-300">
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Nota:</strong> Este pago se registrará como abono a la reserva. 
                            <span class="font-bold text-red-600">Este pago NO libera la habitación automáticamente.</span>
                        </p>
                    </div>
                </div>

                {{-- Error General --}}
                <p x-show="error" x-text="error" class="mb-4 text-xs text-red-600 font-semibold bg-red-50 p-3 rounded-lg"></p>
            </div>

            {{-- Footer --}}
            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    @click="confirm()"
                    :disabled="loading || !canConfirm"
                    class="w-full inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-6 py-3 text-base font-bold text-white focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    :class="confirmButtonClass">
                    <span x-show="!loading" x-text="confirmButtonText"></span>
                    <span x-show="loading" class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Procesando...
                    </span>
                </button>
                <button
                    type="button"
                    @click="cancel()"
                    :disabled="loading"
                    class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@verbatim
<script>
function paymentModal() {
    return {
        isOpen: false,
        title: 'Registrar Pago',
        loading: false,
        error: '',
        cancelText: 'Cancelar',
        confirmButtonClass: 'bg-emerald-600 hover:bg-emerald-700',
        
        // Datos financieros
        financialContext: {
            totalAmount: 0,
            paymentsTotal: 0,
            balanceDue: 0
        },
        
        // Datos del pago
        paymentAmount: 0,
        paymentAmountDisplay: '',
        nightPrice: 0,
        isCustomAmount: false,
        amountError: '',
        
        // Método de pago
        paymentMethod: 'efectivo',
        bankName: '',
        reference: '',
        bankError: '',
        referenceError: '',
        
        // Callbacks
        onConfirm: null,
        reservationId: null,
        
        // Referencia al componente Livewire (se obtendrá cuando se abra el modal)
        livewireComponent: null,

        open(data) {
            this.isOpen = true;
            this.title = data.title || 'Registrar Pago';
            this.financialContext = {
                totalAmount: parseFloat(data.financialContext?.totalAmount || 0),
                paymentsTotal: parseFloat(data.financialContext?.paymentsTotal || 0),
                balanceDue: parseFloat(data.financialContext?.balanceDue || 0)
            };
            this.nightPrice = parseFloat(data.nightPrice || 0);
            this.reservationId = data.reservationId;
            this.onConfirm = data.onConfirm || null;
            
            // Obtener el componente Livewire cuando se abre el modal
            this.livewireComponent = this.getLivewireComponent();
            
            // Resetear campos
            this.paymentAmount = 0;
            this.paymentAmountDisplay = '';
            this.paymentMethod = 'efectivo';
            this.bankName = '';
            this.reference = '';
            this.isCustomAmount = false;
            this.error = '';
            this.amountError = '';
            this.bankError = '';
            this.referenceError = '';
            this.loading = false;
        },
        
        getLivewireComponent() {
            if (!window.Livewire) {
                return null;
            }
            
            // Buscar por wire:id
            const wireElement = document.querySelector('[wire\\:id]');
            if (wireElement) {
                const wireId = wireElement.getAttribute('wire:id');
                if (wireId) {
                    return Livewire.find(wireId);
                }
            }
            
            // Fallback: usar el primer componente
            if (typeof Livewire.all === 'function') {
                const allComponents = Livewire.all();
                if (allComponents.length > 0) {
                    return allComponents[0];
                }
            }
            
            return null;
        },

        setAmount(amount) {
            this.paymentAmount = parseFloat(amount) || 0;
            this.paymentAmountDisplay = this.formatCurrencyInput(this.paymentAmount);
            this.isCustomAmount = false;
            this.validateAmount();
        },

        setCustomAmount() {
            this.isCustomAmount = true;
            this.paymentAmount = 0;
            this.paymentAmountDisplay = '';
        },

        handleAmountInput(event) {
            let value = event.target.value.replace(/[^\d.]/g, '');
            this.paymentAmountDisplay = value;
            this.paymentAmount = parseFloat(value) || 0;
            this.validateAmount();
        },

        validateAmount() {
            this.amountError = '';
            
            if (this.paymentAmount <= 0) {
                this.amountError = 'El monto debe ser mayor a 0';
                return false;
            }
            
            if (this.paymentAmount > this.financialContext.balanceDue) {
                this.amountError = `El monto no puede ser mayor al saldo pendiente (${this.formatCurrency(this.financialContext.balanceDue)})`;
                return false;
            }
            
            return true;
        },

        handlePaymentMethodChange() {
            this.bankName = '';
            this.reference = '';
            this.bankError = '';
            this.referenceError = '';
        },

        validateTransferFields() {
            this.bankError = '';
            this.referenceError = '';
            
            if (this.paymentMethod === 'transferencia') {
                if (!this.bankName || this.bankName.trim() === '') {
                    this.bankError = 'El nombre del banco es requerido';
                }
                if (!this.reference || this.reference.trim() === '') {
                    this.referenceError = 'La referencia/comprobante es requerido';
                }
            }
            
            return !this.bankError && !this.referenceError;
        },

        get canConfirm() {
            if (this.paymentAmount <= 0 || this.amountError) return false;
            if (this.paymentMethod === 'transferencia') {
                return this.bankName.trim() !== '' && this.reference.trim() !== '';
            }
            return true;
        },

        get confirmButtonText() {
            if (this.paymentAmount >= this.financialContext.balanceDue && this.financialContext.balanceDue > 0) {
                return 'Pagar saldo pendiente';
            }
            return 'Registrar abono';
        },

        confirm() {
            console.log('[Payment Modal] confirm() called', {
                paymentAmount: this.paymentAmount,
                paymentMethod: this.paymentMethod,
                reservationId: this.reservationId,
                bankName: this.bankName,
                reference: this.reference
            });
            
            // Validar monto
            if (!this.validateAmount()) {
                console.warn('[Payment Modal] Amount validation failed');
                return;
            }

            // Validar transferencia si aplica
            if (this.paymentMethod === 'transferencia') {
                if (!this.validateTransferFields()) {
                    console.warn('[Payment Modal] Transfer fields validation failed');
                    this.error = 'Por favor complete todos los campos de transferencia';
                    return;
                }
            }

            this.loading = true;
            this.error = '';
            console.log('[Payment Modal] Setting loading = true');

            // Preparar datos
            const paymentData = {
                reservationId: this.reservationId,
                amount: this.paymentAmount,
                paymentMethod: this.paymentMethod,
                bankName: this.paymentMethod === 'transferencia' ? this.bankName : null,
                reference: this.paymentMethod === 'transferencia' ? this.reference : null
            };

            console.log('[Payment Modal] Dispatching register-payment-event', paymentData);

            // Enviar evento DOM personalizado que el listener en scripts.blade.php capturará
            try {
                window.dispatchEvent(new CustomEvent('register-payment-event', {
                    detail: {
                        reservationId: paymentData.reservationId,
                        amount: paymentData.amount,
                        paymentMethod: paymentData.paymentMethod,
                        bankName: paymentData.bankName,
                        reference: paymentData.reference
                    }
                }));
                console.log('[Payment Modal] Payment event dispatched successfully');
                // El listener en scripts.blade.php manejará la llamada al método
            } catch (e) {
                console.error('[Payment Modal] Exception dispatching payment event:', e);
                this.error = 'Error al procesar el pago: ' + e.message;
                this.loading = false;
            }
        },

        cancel() {
            this.isOpen = false;
            this.error = '';
            this.loading = false;
        },

        init() {
            // Escuchar evento para cerrar el modal desde el servidor
            window.addEventListener('close-payment-modal', () => {
                console.log('[Payment Modal] close-payment-modal event received');
                this.cancel();
            });
            
            // Escuchar evento para resetear loading en caso de error
            window.addEventListener('reset-payment-modal-loading', (event) => {
                console.error('[Payment Modal] reset-payment-modal-loading event received', {
                    timestamp: new Date().toISOString(),
                    event: event,
                    currentState: {
                        loading: this.loading,
                        error: this.error,
                        paymentAmount: this.paymentAmount,
                        paymentMethod: this.paymentMethod,
                        reservationId: this.reservationId
                    },
                    stackTrace: new Error().stack
                });
                this.loading = false;
                this.error = 'Error al procesar el pago. Por favor, intente nuevamente.';
            });
            
            // Escuchar eventos de Livewire para cerrar el modal después del pago
            document.addEventListener('livewire:init', () => {
                Livewire.on('payment-registered', () => {
                    console.log('[Payment Modal] payment-registered event received from Livewire');
                    this.loading = false;
                    this.cancel();
                });
            });
            
            // También escuchar si Livewire ya está inicializado
            if (window.Livewire) {
                Livewire.on('payment-registered', () => {
                    console.log('[Payment Modal] payment-registered event received from Livewire (already initialized)');
                    this.loading = false;
                    this.cancel();
                });
            }
            
            console.log('[Payment Modal] Initialized with event listeners');
        },

        formatCurrency(amount) {
            if (!amount || amount === 0) return '$0';
            return '$' + new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },

        formatCurrencyInput(amount) {
            if (!amount || amount === 0) return '';
            return amount.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endverbatim
