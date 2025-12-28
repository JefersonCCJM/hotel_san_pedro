{{-- Room Manager Header Component --}}
<div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                <i class="fas fa-door-open text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Habitaciones</h1>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs sm:text-sm text-gray-500">
                        <span class="font-semibold text-gray-900">{{ $rooms->total() }}</span> habitaciones
                        registradas
                    </span>
                    <span class="text-gray-300 hidden sm:inline">•</span>
                    <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                        <i class="fas fa-chart-line mr-1"></i> Panel de control
                    </span>
                </div>
            </div>
        </div>

        <a href="{{ route('rooms.create') }}"
            class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
            <i class="fas fa-plus mr-2"></i>
            <span>Nueva Habitación</span>
        </a>
    </div>
</div>

