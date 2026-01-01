@props([
    'eventName' => 'confirm-delete',
    'title' => 'Eliminar Producto',
    'message' => '¿Estás seguro de que deseas eliminar',
    'confirmMethod' => 'deleteProduct',
    'itemNameAttribute' => 'name',
])

<div x-data="{ 
        show: false, 
        itemId: null, 
        itemName: '',
        init() {
            window.addEventListener('{{ $eventName }}', (e) => {
                this.itemId = e.detail.id;
                this.itemName = e.detail['{{ $itemNameAttribute }}'];
                this.show = true;
            });
        },
        confirm() {
            @this.call('{{ $confirmMethod }}', this.itemId);
            this.show = false;
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
             class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            <div class="bg-white px-6 pt-6 pb-4 sm:p-8">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-14 w-14 rounded-2xl bg-rose-50 sm:mx-0 sm:h-12 sm:w-12">
                        <i class="fas fa-exclamation-triangle text-rose-600 text-xl"></i>
                    </div>
                    <div class="mt-4 text-center sm:mt-0 sm:ml-6 sm:text-left">
                        <h3 class="text-xl leading-6 font-black text-gray-900" id="modal-title">
                            {{ $title }}
                        </h3>
                        <div class="mt-3">
                            <p class="text-sm text-gray-500 leading-relaxed">
                                {{ $message }} <span class="font-bold text-gray-900" x-text="itemName"></span>? 
                                Esta acción no se podrá deshacer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50/50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                <button type="button" 
                        @click="confirm()"
                        class="w-full inline-flex justify-center items-center px-6 py-3 rounded-xl border border-transparent shadow-sm text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all duration-200">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Confirmar Eliminación
                </button>
                <button type="button" 
                        @click="show = false"
                        class="mt-3 sm:mt-0 w-full inline-flex justify-center items-center px-6 py-3 rounded-xl border border-gray-200 shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

