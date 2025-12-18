@extends('layouts.app')

@section('title', 'Nueva Reserva')
@section('header', 'Nueva Reserva')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fas fa-calendar-plus text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nueva Reserva</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Registra una nueva reserva de habitación</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('reservations.store') }}" class="space-y-6">
        @csrf

        <!-- Información de la Reserva -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cliente -->
                <div>
                    <label for="customer_id" class="block text-sm font-semibold text-gray-700 mb-2">Cliente <span class="text-red-500">*</span></label>
                    <select name="customer_id" id="customer_id" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                        <option value="">Seleccione un cliente...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Habitación -->
                <div>
                    <label for="room_id" class="block text-sm font-semibold text-gray-700 mb-2">Habitación <span class="text-red-500">*</span></label>
                    <select name="room_id" id="room_id" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                        <option value="">Seleccione una habitación...</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->room_number }} - {{ $room->room_type }} (${{ number_format($room->price_per_night, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    <div id="availability-status" class="mt-2 text-xs hidden"></div>
                    @error('room_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fecha Reserva -->
                <div>
                    <label for="reservation_date" class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Reserva <span class="text-red-500">*</span></label>
                    <input type="date" name="reservation_date" id="reservation_date" value="{{ old('reservation_date', date('Y-m-d')) }}" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                    @error('reservation_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Fecha Entrada -->
                <div>
                    <label for="check_in_date" class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Entrada <span class="text-red-500">*</span></label>
                    <input type="date" name="check_in_date" id="check_in_date" value="{{ old('check_in_date') }}" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                    @error('check_in_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Fecha Salida -->
                <div>
                    <label for="check_out_date" class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Salida <span class="text-red-500">*</span></label>
                    <input type="date" name="check_out_date" id="check_out_date" value="{{ old('check_out_date') }}" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                    @error('check_out_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Valor Total -->
                <div>
                    <label for="total_amount" class="block text-sm font-semibold text-gray-700 mb-2">Valor Total <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" step="1" name="total_amount" id="total_amount" value="{{ old('total_amount') }}" class="block w-full pl-8 border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="0" required>
                    </div>
                    @error('total_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Abono -->
                <div>
                    <label for="deposit" class="block text-sm font-semibold text-gray-700 mb-2">Abono <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        <input type="number" step="1" name="deposit" id="deposit" value="{{ old('deposit', 0) }}" class="block w-full pl-8 border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="0" required>
                    </div>
                    @error('deposit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Observaciones -->
            <div>
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">Observaciones</label>
                <textarea name="notes" id="notes" rows="3" class="block w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="Notas adicionales...">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Botones -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('reservations.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-all">Cancelar</a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all shadow-sm">Crear Reserva</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const roomSelect = document.getElementById('room_id');
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    const statusDiv = document.getElementById('availability-status');

    function checkAvailability() {
        if (roomSelect.value && checkIn.value && checkOut.value) {
            fetch(`{{ route('api.check-availability') }}?room_id=${roomSelect.value}&check_in_date=${checkIn.value}&check_out_date=${checkOut.value}`)
                .then(response => response.json())
                .then(data => {
                    statusDiv.classList.remove('hidden');
                    if (data.available) {
                        statusDiv.innerHTML = '<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Habitación disponible</span>';
                    } else {
                        statusDiv.innerHTML = '<span class="text-red-600 font-bold"><i class="fas fa-times-circle mr-1"></i> NO DISPONIBLE para estas fechas</span>';
                    }
                });
        } else {
            statusDiv.classList.add('hidden');
        }
    }

    [roomSelect, checkIn, checkOut].forEach(el => el.addEventListener('change', checkAvailability));
</script>
@endpush
@endsection

