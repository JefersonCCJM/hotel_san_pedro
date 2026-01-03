<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-xl rounded-xl bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Cancelar Reserva</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">¿Estás seguro de cancelar esta reserva? Esta acción no se puede deshacer.</p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="delete-form" method="POST" onsubmit="event.preventDefault(); confirmDeleteWithPin(this);">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white text-base font-medium rounded-lg w-full shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-300">Aceptar</button>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-lg w-full border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

