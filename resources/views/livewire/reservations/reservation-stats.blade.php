<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
    <!-- Total Reservations -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reservas Totales</p>
                <p class="text-2xl font-black text-gray-900">{{ $this->totalReservations }}</p>
            </div>
            <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                <i class="fas fa-calendar-alt text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Reservations -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reservas Vigentes</p>
                <p class="text-2xl font-black text-green-600">{{ $this->activeReservations }}</p>
            </div>
            <div class="p-3 rounded-xl bg-green-50 text-green-600">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Cancelled Reservations -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reservas Canceladas</p>
                <p class="text-2xl font-black text-red-600">{{ $this->cancelledReservations }}</p>
            </div>
            <div class="p-3 rounded-xl bg-red-50 text-red-600">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Occupied Rooms Today -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Habitaciones Ocupadas</p>
                <p class="text-2xl font-black text-indigo-600">{{ $this->occupiedRoomsToday }}</p>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600">
                <i class="fas fa-door-open text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Reservations Today -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reservas para Hoy</p>
                <p class="text-2xl font-black text-purple-600">{{ $this->reservationsToday }}</p>
            </div>
            <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                <i class="fas fa-calendar-day text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Guests Today -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Huéspedes Planificados</p>
                <p class="text-2xl font-black text-orange-600">{{ $this->totalGuestsToday }}</p>
            </div>
            <div class="p-3 rounded-xl bg-orange-50 text-orange-600">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
    </div>
</div>




