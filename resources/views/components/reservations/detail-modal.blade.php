<div id="reservation-detail-modal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50 transition-all duration-300">
    <div class="relative top-10 mx-auto p-0 border-0 w-full max-w-lg shadow-2xl rounded-3xl bg-white overflow-hidden transform transition-all">
        <!-- Header del Modal -->
        <div class="bg-emerald-600 px-6 py-8 text-white relative">
            <button onclick="closeReservationDetail()" class="absolute top-4 right-4 text-white/80 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shadow-inner">
                    <i class="fas fa-bookmark"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black tracking-tight" id="modal-customer-name">Cargando...</h3>
                    <p class="text-emerald-100 font-bold text-sm uppercase tracking-widest opacity-80" id="modal-reservation-id"></p>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="p-8 space-y-8">
            <!-- Grid de Información -->
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Habitación</span>
                    <div class="flex items-center text-gray-900">
                        <i class="fas fa-door-open mr-2 text-emerald-500"></i>
                        <span class="font-bold" id="modal-room-info"></span>
                    </div>
                </div>
                <div class="space-y-1 text-right">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estancia</span>
                    <div class="flex items-center justify-end text-gray-900">
                        <i class="fas fa-calendar-alt mr-2 text-emerald-500"></i>
                        <span class="font-bold" id="modal-dates"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Hora de ingreso</span>
                    <div class="flex items-center text-gray-900">
                        <i class="fas fa-clock mr-2 text-emerald-500"></i>
                        <span class="font-bold" id="modal-checkin-time"></span>
                    </div>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Huéspedes</span>
                    <div class="flex items-center text-gray-900">
                        <i class="fas fa-users mr-2 text-emerald-500"></i>
                        <span class="font-bold" id="modal-guests-count"></span>
                    </div>
                </div>
                <div class="space-y-1 text-right">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Método de pago</span>
                    <div class="flex items-center justify-end text-gray-900">
                        <i class="fas fa-credit-card mr-2 text-emerald-500"></i>
                        <span class="font-bold" id="modal-payment-method"></span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-gray-50 rounded-3xl space-y-4 border border-gray-100 shadow-inner">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-xs font-bold text-gray-500 uppercase">Total de Reserva</span>
                    <span class="text-xl font-black text-gray-900" id="modal-total"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Abono Realizado</span>
                    <span class="text-sm font-black text-emerald-600" id="modal-deposit"></span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                    <span class="text-xs font-black text-red-500 uppercase">Saldo Pendiente</span>
                    <span class="text-lg font-black text-red-600 bg-red-50 px-3 py-1 rounded-xl" id="modal-balance"></span>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Notas</span>
                <p class="text-sm text-gray-600 italic bg-gray-50 p-4 rounded-2xl border border-dashed border-gray-200" id="modal-notes"></p>
            </div>

            <!-- Acciones -->
            <div class="grid grid-cols-3 gap-3 pt-4">
                <a id="modal-edit-btn" href="#" class="flex flex-col items-center justify-center p-4 bg-indigo-50 text-indigo-600 rounded-2xl hover:bg-indigo-100 transition-all group">
                    <i class="fas fa-edit mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] font-black uppercase">Editar</span>
                </a>
                <a id="modal-pdf-btn" href="#" class="flex flex-col items-center justify-center p-4 bg-red-50 text-red-600 rounded-2xl hover:bg-red-100 transition-all group">
                    <i class="fas fa-file-pdf mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] font-black uppercase">PDF</span>
                </a>
                <button id="modal-delete-btn" onclick="" class="flex flex-col items-center justify-center p-4 bg-orange-50 text-orange-600 rounded-2xl hover:bg-orange-100 transition-all group">
                    <i class="fas fa-ban mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] font-black uppercase">Cancelar</span>
                </button>
            </div>
        </div>
    </div>
</div>

