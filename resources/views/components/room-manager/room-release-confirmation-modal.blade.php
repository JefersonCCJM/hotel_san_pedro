@props(['room', 'date'])

<div x-data="{ 
        show: false, 
        roomData: null,
        paymentConfirmed: false,
        refundConfirmed: false,
        init() {
            window.addEventListener('open-release-confirmation', (e) => {
                this.roomData = e.detail;
                this.show = true;
                this.paymentConfirmed = false;
                this.refundConfirmed = false;
            });
        }
     }" 
     x-show="show" 
     x-cloak
     class="fixed inset-0 z-[100] overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" 
             @click="show = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Content -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            <template x-if="roomData">
                <div>
                    <!-- Header -->
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-8 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="fas fa-door-open text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900" id="modal-title">
                                        <template x-if="roomData.cancel_url || roomData.is_cancellation">Cancelar Reserva - Habitación #<span x-text="roomData.room_number"></span></template>
                                        <template x-if="!roomData.cancel_url && !roomData.is_cancellation">Liberar Habitación #<span x-text="roomData.room_number"></span></template>
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <template x-if="roomData.cancel_url || roomData.is_cancellation">Confirme la información antes de cancelar la reserva</template>
                                        <template x-if="!roomData.cancel_url && !roomData.is_cancellation">Confirme la información antes de liberar la habitación</template>
                                    </p>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="show = false"
                                    class="text-gray-400 hover:text-gray-900">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white px-6 pb-4 sm:p-8 max-h-[calc(100vh-300px)] overflow-y-auto">
                        <div class="space-y-6">
                            <!-- Información del Cliente -->
                            <template x-if="roomData.reservation">
                                <div class="bg-gray-50 rounded-xl p-6">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Información del Cliente</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Nombre</p>
                                            <p class="text-sm font-bold text-gray-900" x-text="roomData.reservation?.customer?.name || 'N/A'"></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Identificación</p>
                                            <p class="text-sm font-bold text-gray-900" x-text="roomData.identification || 'N/A'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Resumen Financiero -->
                            <template x-if="roomData.reservation">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Resumen Financiero</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Hospedaje</p>
                                            <p class="text-lg font-black text-gray-900" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(roomData.total_hospedaje || 0)"></p>
                                        </div>
                                        <div class="p-4 bg-green-50 rounded-xl text-center">
                                            <p class="text-[9px] font-bold text-green-600 uppercase mb-1">Abono Realizado</p>
                                            <p class="text-lg font-black text-green-700" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(roomData.abono_realizado || 0)"></p>
                                        </div>
                                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Total Consumos</p>
                                            <p class="text-lg font-black text-gray-900" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(roomData.sales_total || 0)"></p>
                                        </div>
                                        <div class="p-4 rounded-xl text-center" 
                                             :class="(roomData.total_debt || 0) > 0 ? 'bg-red-50' : (roomData.total_debt || 0) < 0 ? 'bg-blue-50' : 'bg-emerald-50'">
                                            <p class="text-[9px] font-bold uppercase mb-1"
                                               :class="(roomData.total_debt || 0) > 0 ? 'text-red-600' : (roomData.total_debt || 0) < 0 ? 'text-blue-600' : 'text-emerald-600'">
                                                <template x-if="(roomData.total_debt || 0) > 0">Deuda Pendiente</template>
                                                <template x-if="(roomData.total_debt || 0) < 0">Se Le Debe al Cliente</template>
                                                <template x-if="(roomData.total_debt || 0) === 0">Al Día</template>
                                            </p>
                                            <p class="text-lg font-black"
                                               :class="(roomData.total_debt || 0) > 0 ? 'text-red-700' : (roomData.total_debt || 0) < 0 ? 'text-blue-700' : 'text-emerald-700'"
                                               x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(Math.abs(roomData.total_debt || 0))"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Consumos -->
                            <template x-if="roomData.reservation && roomData.sales && roomData.sales.length > 0">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Consumos</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-[9px] font-bold text-gray-400 uppercase">Producto</th>
                                                    <th class="px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase">Cantidad</th>
                                                    <th class="px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase">Estado</th>
                                                    <th class="px-4 py-3 text-right text-[9px] font-bold text-gray-400 uppercase">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-50">
                                                <template x-for="sale in roomData.sales" :key="sale.id">
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="sale.product?.name || 'N/A'"></td>
                                                        <td class="px-4 py-3 text-sm text-center font-bold text-gray-500" x-text="sale.quantity"></td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="text-[9px] font-bold uppercase px-2 py-1 rounded-full"
                                                                  :class="sale.is_paid ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50'"
                                                                  x-text="sale.is_paid ? sale.payment_method : 'Pendiente'"></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-right font-black text-gray-900" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(sale.total)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <!-- Historial de Abonos -->
                            <template x-if="roomData.reservation && roomData.deposit_history && roomData.deposit_history.length > 0">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Historial de Abonos</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-[9px] font-bold text-gray-400 uppercase">Fecha</th>
                                                    <th class="px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase">Monto</th>
                                                    <th class="px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase">Método</th>
                                                    <th class="px-4 py-3 text-left text-[9px] font-bold text-gray-400 uppercase">Notas</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-50">
                                                <template x-for="deposit in roomData.deposit_history" :key="deposit.id">
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="deposit.created_at"></td>
                                                        <td class="px-4 py-3 text-sm text-center font-bold text-gray-900" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(deposit.amount)"></td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="text-[9px] font-bold uppercase px-2 py-1 rounded-full"
                                                                  :class="deposit.payment_method === 'efectivo' ? 'text-emerald-600 bg-emerald-50' : 'text-blue-600 bg-blue-50'"
                                                                  x-text="deposit.payment_method"></span>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="deposit.notes || '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <!-- Historial de Devoluciones -->
                            <template x-if="roomData.reservation && roomData.refunds_history && roomData.refunds_history.length > 0">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Historial de Devoluciones</h4>
                                        <span class="text-[9px] text-gray-500 font-medium">Total: <strong x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(roomData.total_refunds || 0)"></strong></span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-[9px] font-bold text-gray-400 uppercase">Fecha</th>
                                                    <th class="px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase">Monto</th>
                                                    <th class="px-4 py-3 text-left text-[9px] font-bold text-gray-400 uppercase">Registrado Por</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-50">
                                                <template x-for="refund in roomData.refunds_history" :key="refund.id">
                                                    <tr>
                                                        <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="refund.created_at"></td>
                                                        <td class="px-4 py-3 text-sm text-center font-bold text-blue-600" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(refund.amount)"></td>
                                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="refund.created_by"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <!-- Advertencia si hay deuda -->
                            <template x-if="(roomData.total_debt || 0) > 0">
                                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-sm font-black text-red-900 mb-2">¡Atención! La habitación tiene deuda pendiente</h5>
                                            <p class="text-sm text-red-700 mb-4">
                                                La habitación tiene una deuda de <strong x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(roomData.total_debt)"></strong>.
                                                Debe confirmar que se realizó el pago antes de liberar la habitación.
                                            </p>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" 
                                                       x-model="paymentConfirmed"
                                                       class="w-5 h-5 text-red-600 border-red-300 rounded focus:ring-red-500">
                                                <span class="text-sm font-bold text-red-900">Confirmo que se realizó el pago de la deuda</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Advertencia si hay saldo a favor y no se registró devolución -->
                            <template x-if="(roomData.total_debt || 0) < 0 && (!roomData.refunds_history || roomData.refunds_history.length === 0)">
                                <div class="bg-orange-50 border-2 border-orange-300 rounded-xl p-6">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-orange-600 text-2xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-sm font-black text-orange-900 mb-2">¡Atención! Se le debe dinero al cliente</h5>
                                            <p class="text-sm text-orange-800 mb-4">
                                                El cliente tiene un saldo a favor de <strong class="text-orange-900" x-text="'$' + new Intl.NumberFormat('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0}).format(Math.abs(roomData.total_debt))"></strong>.
                                                <strong>Debe registrar la devolución antes de liberar la habitación.</strong>
                                            </p>
                                            <div class="space-y-3">
                                                <button type="button"
                                                        @click="if ($wire) {
                                                            $wire.call('registerCustomerRefund', roomData.reservation.id).then(() => {
                                                                $wire.call('loadRoomReleaseData', roomData.room_id).then((updatedData) => {
                                                                    roomData = updatedData;
                                                                    refundConfirmed = true;
                                                                });
                                                            });
                                                        }"
                                                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition-colors">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    Registrar Devolución de Dinero
                                                </button>
                                                <p class="text-xs text-orange-700 mt-2">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Esta acción quedará registrada en el historial de auditoría y no se puede deshacer.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Sin deuda o saldo a favor ya registrado -->
                            <template x-if="(roomData.total_debt || 0) === 0 || ((roomData.total_debt || 0) < 0 && roomData.refunds_history && roomData.refunds_history.length > 0)">
                                <div class="bg-emerald-50 border-2 border-emerald-200 rounded-xl p-6">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-check-circle text-emerald-600 text-2xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-sm font-black text-emerald-900 mb-2">La habitación está al día</h5>
                                            <p class="text-sm text-emerald-700">
                                                <template x-if="(roomData.total_debt || 0) < 0">
                                                    La devolución de dinero ha sido registrada. Puede proceder a liberar la habitación.
                                                </template>
                                                <template x-if="(roomData.total_debt || 0) === 0">
                                                    No hay deuda pendiente. Puede proceder a liberar la habitación.
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Sin reserva -->
                            <template x-if="!roomData.reservation">
                                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h5 class="text-sm font-black text-blue-900 mb-2">Habitación sin reserva activa</h5>
                                            <p class="text-sm text-blue-700">
                                                Esta habitación no tiene una reserva activa. Puede proceder a liberarla.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3 border-t border-gray-100">
                        <button type="button" 
                                @click="
                                    if ((roomData.total_debt || 0) > 0 && !paymentConfirmed) {
                                        return;
                                    }
                                    if ((roomData.total_debt || 0) < 0 && (!roomData.refunds_history || roomData.refunds_history.length === 0)) {
                                        return;
                                    }
                                    if (roomData.cancel_url) {
                                        // Cancel reservation from reservations module
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = roomData.cancel_url;
                                        const csrf = document.createElement('input');
                                        csrf.type = 'hidden';
                                        csrf.name = '_token';
                                        csrf.value = '{{ csrf_token() }}';
                                        form.appendChild(csrf);
                                        const method = document.createElement('input');
                                        method.type = 'hidden';
                                        method.name = '_method';
                                        method.value = 'DELETE';
                                        form.appendChild(method);
                                        document.body.appendChild(form);
                                        form.submit();
                                    } else if (roomData.is_cancellation) {
                                        // Cancel reservation from room manager
                                        if ($wire) {
                                            $wire.call('cancelReservation', roomData.room_id);
                                        }
                                    } else {
                                        // Release room from room manager
                                        if ($wire) {
                                            $wire.call('releaseRoom', roomData.room_id, 'libre');
                                        }
                                    }
                                    show = false;
                                "
                                :disabled="((roomData.total_debt || 0) > 0 && !paymentConfirmed) || ((roomData.total_debt || 0) < 0 && (!roomData.refunds_history || roomData.refunds_history.length === 0))"
                                :class="((roomData.total_debt || 0) > 0 && !paymentConfirmed) || ((roomData.total_debt || 0) < 0 && (!roomData.refunds_history || roomData.refunds_history.length === 0))
                                    ? 'bg-gray-400 cursor-not-allowed' 
                                    : 'bg-emerald-600 hover:bg-emerald-700'"
                                class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3 rounded-xl border border-transparent shadow-sm text-sm font-bold text-white transition-all duration-200">
                            <i class="fas fa-check mr-2"></i>
                            <template x-if="roomData.cancel_url || roomData.is_cancellation">Confirmar Cancelación</template>
                            <template x-if="!roomData.cancel_url && !roomData.is_cancellation">Confirmar Liberación</template>
                        </button>
                        <button type="button" 
                                @click="show = false"
                                class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 rounded-xl border border-gray-200 shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                            Cancelar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

