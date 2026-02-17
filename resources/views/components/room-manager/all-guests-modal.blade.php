@props(['allGuestsForm'])

@if($allGuestsForm)
<div x-data="{
    show: @entangle('allGuestsModal'),
    addingGuest: false,
    canEdit: @js((bool)($allGuestsForm['can_edit'] ?? true)),
    newGuestName: '',
    newGuestIdentification: '',
    newGuestPhone: '',
    maxCapacity: {{ $allGuestsForm['max_capacity'] ?? 4 }},
    currentGuestCount: {{ count($allGuestsForm['guests'] ?? []) }},
    init() {
        this.$watch('show', (value) => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });
    },
    get canAddGuest() {
        return this.currentGuestCount < this.maxCapacity;
    },
    get remainingCapacity() {
        return this.maxCapacity - this.currentGuestCount;
    },
    startAddingGuest() {
        if (!this.canEdit) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'No se puede editar informaciÃ³n de fechas pasadas.' }
            }));
            return;
        }

        if (!this.canAddGuest) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'Capacidad mÃ¡xima alcanzada' }
            }));
            return;
        }
        this.addingGuest = true;
        this.newGuestName = '';
        this.newGuestIdentification = '';
        this.newGuestPhone = '';
    },
    cancelAddingGuest() {
        this.addingGuest = false;
        this.newGuestName = '';
        this.newGuestIdentification = '';
        this.newGuestPhone = '';
    },
    saveGuest() {
        if (!this.canEdit) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'No se puede editar informaciÃ³n de fechas pasadas.' }
            }));
            return;
        }

        if (!this.newGuestName.trim()) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'El nombre del huÃ©sped es requerido' }
            }));
            return;
        }
        
        $wire.call('addGuestToRoom', {
            reservation_id: {{ $allGuestsForm['reservation_id'] }},
            room_id: {{ $allGuestsForm['room_id'] }},
            name: this.newGuestName,
            identification: this.newGuestIdentification,
            phone: this.newGuestPhone
        }).then(() => {
            this.addingGuest = false;
            this.newGuestName = '';
            this.newGuestIdentification = '';
            this.newGuestPhone = '';
        }).catch(() => {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'No fue posible agregar el huÃ©sped.' }
            }));
        });
    },
    close() {
        this.show = false;
        $wire.set('allGuestsModal', false);
    }
}" x-show="show" 
    x-cloak
    class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50"
    style="display: none;">
    
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Todos los HuÃ©spedes</h3>
                            <p class="text-blue-100 text-sm">HabitaciÃ³n: {{ $allGuestsForm['room_id'] ?? 'N/A' }}</p>
                            <p class="text-blue-100 text-xs">Capacidad: <span x-text="currentGuestCount"></span>/<span x-text="maxCapacity"></span></p>
                        </div>
                    </div>
                    <button @click="close()" class="text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 max-h-[60vh] overflow-y-auto">
                @if(isset($allGuestsForm['guests']) && count($allGuestsForm['guests']) > 0)
                    <div class="space-y-4 mb-6">
                        @foreach($allGuestsForm['guests'] as $guest)
                            <div class="border rounded-xl p-4 @if($guest['is_primary']) border-blue-200 bg-blue-50 @else border-gray-200 bg-gray-50 @endif">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-10 h-10 rounded-full @if($guest['is_primary']) bg-blue-100 text-blue-600 @else bg-gray-100 text-gray-600 @endif flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <h4 class="font-semibold text-gray-900">{{ $guest['name'] }}</h4>
                                                @if($guest['is_primary'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Principal
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Adicional
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="space-y-1 text-sm text-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <i class="fas fa-id-card text-gray-400"></i>
                                                    <span>{{ $guest['identification'] }}</span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <i class="fas fa-phone text-gray-400"></i>
                                                    <span>{{ $guest['phone'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 mb-6">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <i class="fas fa-users text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500">No hay huÃ©spedes asignados a esta habitaciÃ³n</p>
                    </div>
                @endif

                <!-- BotÃ³n para agregar huÃ©sped -->
                <div class="border-t pt-4">
                    @if(!($allGuestsForm['can_edit'] ?? true))
                        <div class="text-center text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 mb-3">
                            Vista historica: solo lectura.
                        </div>
                    @endif

                    <div x-show="!addingGuest && canEdit" class="text-center">
                        <button @click="startAddingGuest()" 
                                :disabled="!canAddGuest"
                                :class="canAddGuest ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                                class="px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2 mx-auto">
                            <i class="fas fa-user-plus"></i>
                            <span>Agregar HuÃ©sped</span>
                            <span x-show="!canAddGuest" class="text-xs">(Capacidad mÃ¡xima alcanzada)</span>
                        </button>
                        <div x-show="canAddGuest" class="text-xs text-gray-500 mt-2">
                            <span x-text="remainingCapacity"></span> plazas disponibles
                        </div>
                    </div>

                    <!-- Formulario para agregar huÃ©sped (usando Select2 como en assign-guests-modal) -->
                    <div x-show="addingGuest && canEdit" class="bg-gray-50 rounded-lg p-4" x-data="{ showGuestSearch: false }">
                        <h4 class="font-semibold text-gray-900 mb-3">Agregar HuÃ©sped Adicional</h4>
                        
                        <div class="space-y-3">
                            <!-- OpciÃ³n 1: Buscar cliente existente -->
                            <div x-show="!showGuestSearch">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Buscar Cliente Existente</label>
                                    <button type="button" 
                                            @click="showGuestSearch = true"
                                            class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter flex items-center gap-1">
                                        <i class="fas fa-search text-[8px]"></i>
                                        Buscar
                                    </button>
                                </div>
                                <div class="text-center text-xs text-gray-500 py-4">
                                    <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
                                    <p>Click en "Buscar" para encontrar un cliente existente</p>
                                </div>
                            </div>

                            <!-- Selector de bÃºsqueda (Select2) -->
                            <div x-show="showGuestSearch" 
                                 x-transition
                                 x-init="setTimeout(() => { 
                                    const event = new CustomEvent('init-all-guests-additional-guest-select');
                                    document.dispatchEvent(event);
                                 }, 100)"
                                 class="space-y-2 p-3 bg-white rounded-lg border border-gray-200" 
                                 x-cloak>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Buscar Cliente</label>
                                    <button type="button" 
                                            @click="showGuestSearch = false; if (typeof window.allGuestsAdditionalGuestSelect !== 'undefined' && window.allGuestsAdditionalGuestSelect) { window.allGuestsAdditionalGuestSelect.destroy(); window.allGuestsAdditionalGuestSelect = null; }"
                                            class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                                <div wire:ignore>
                                    <select
                                        id="all_guests_additional_guest_customer_id"
                                        class="w-full"
                                        data-reservation-id="{{ $allGuestsForm['reservation_id'] }}"
                                        data-room-id="{{ $allGuestsForm['room_id'] }}"
                                    ></select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" 
                                            @click="showGuestSearch = false; if (typeof window.allGuestsAdditionalGuestSelect !== 'undefined' && window.allGuestsAdditionalGuestSelect) { window.allGuestsAdditionalGuestSelect.destroy(); window.allGuestsAdditionalGuestSelect = null; }"
                                            class="flex-1 px-3 py-1.5 text-[10px] font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="button" 
                                            @click="$dispatch('open-create-customer-modal-for-additional'); showGuestSearch = false; if (typeof window.allGuestsAdditionalGuestSelect !== 'undefined' && window.allGuestsAdditionalGuestSelect) { window.allGuestsAdditionalGuestSelect.destroy(); window.allGuestsAdditionalGuestSelect = null; }"
                                            class="flex-1 px-3 py-1.5 text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                        <i class="fas fa-plus mr-1 text-[8px]"></i>
                                        Crear Nuevo
                                    </button>
                                </div>
                            </div>

                            <!-- OpciÃ³n 2: Crear nuevo cliente directamente -->
                            <div class="border-t pt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">O Crear Nuevo Cliente</label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                        <input type="text" 
                                               x-model="newGuestName"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Nombre completo">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">IdentificaciÃ³n</label>
                                        <input type="text" 
                                               x-model="newGuestIdentification"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="NÃºmero de identificaciÃ³n">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">TelÃ©fono</label>
                                        <input type="text" 
                                               x-model="newGuestPhone"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="NÃºmero de telÃ©fono">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-4 pt-3 border-t">
                            <button @click="cancelAddingGuest()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Cancelar
                            </button>
                            <button @click="saveGuest()" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-end space-x-3">
                    <button @click="close()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

