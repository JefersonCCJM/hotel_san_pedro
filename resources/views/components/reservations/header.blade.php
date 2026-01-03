@props([
    'totalReservations'
])

<div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600 shadow-inner">
                <i class="fas fa-calendar-check text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Reservas</h1>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs sm:text-sm text-gray-500">
                        <span class="font-semibold text-gray-900">{{ $totalReservations }}</span> reservas registradas
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <form method="GET"
                  action="{{ route('reservations.export.monthly') }}"
                  class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <label for="export-start-date" class="text-sm font-semibold text-gray-700">Desde:</label>
                    <input id="export-start-date"
                           type="date"
                           name="start_date"
                           value="{{ request('start_date', \Carbon\Carbon::now()->subMonths(3)->format('Y-m-d')) }}"
                           min="{{ \Carbon\Carbon::now()->subYears(2)->format('Y-m-d') }}"
                           max="{{ \Carbon\Carbon::now()->addYears(1)->format('Y-m-d') }}"
                           required
                           class="h-[42px] px-3 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm" />
                </div>

                <div class="flex items-center gap-2">
                    <label for="export-end-date" class="text-sm font-semibold text-gray-700">Hasta:</label>
                    <input id="export-end-date"
                           type="date"
                           name="end_date"
                           value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                           min="{{ \Carbon\Carbon::now()->subYears(2)->format('Y-m-d') }}"
                           max="{{ \Carbon\Carbon::now()->addYears(1)->format('Y-m-d') }}"
                           required
                           class="h-[42px] px-3 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm" />
                </div>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-red-600 bg-white text-red-600 text-sm font-semibold hover:bg-red-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm hover:shadow-md"
                        title="Exportar reporte en PDF">
                    <i class="fas fa-file-pdf mr-2"></i>
                    <span>Exportar PDF</span>
                </button>
            </form>
            <a href="{{ route('reservations.create') }}"
               class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i>
                <span>Nueva Reserva</span>
            </a>
        </div>
    </div>
</div>

