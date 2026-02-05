<div x-show="$wire.isOpen" 
     x-transition
     class="fixed inset-0 z-[60] overflow-y-auto" 
     x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="$wire.close()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i class="fas fa-trash"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Eliminar Cliente</h3>
                </div>
                <button @click="$wire.close()" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">¿Está seguro?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Está a punto de eliminar al cliente <strong>{{ $customerName }}</strong>. Esta acción no se puede deshacer.
                    </p>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-amber-600 mt-0.5 mr-3"></i>
                            <div class="text-sm text-amber-800">
                                <p class="font-semibold mb-1">Importante:</p>
                                <p class="text-xs">El cliente será eliminado permanentemente del sistema. Si tiene reservas asociadas, no podrá ser eliminado.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" 
                                wire:click="close"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancelar
                        </button>
                        <button type="button" 
                                wire:click="delete"
                                wire:loading.attr="disabled"
                                wire:target="delete"
                                class="flex-1 px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="delete">Eliminar</span>
                            <span wire:loading wire:target="delete">
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                Eliminando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
