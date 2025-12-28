@extends('layouts.app')

@section('title', 'Reservas')
@section('header', 'Gestión de Reservas')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
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
                            <span class="font-semibold text-gray-900">{{ $reservations->total() }}</span> reservas registradas
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <form method="GET"
                      action="{{ route('reservations.export.monthly') }}"
                      class="flex items-center gap-2">
                    <label for="monthly-report-month" class="sr-only">Mes del reporte</label>
                    <input id="monthly-report-month"
                           type="month"
                           name="month"
                           value="{{ $date->format('Y-m') }}"
                           required
                           class="h-[42px] px-3 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm" />
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-red-600 bg-white text-red-600 text-sm font-semibold hover:bg-red-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm hover:shadow-md"
                            title="Exportar reporte mensual en PDF">
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

    <!-- Statistics Cards -->
    @livewire('reservations.reservation-stats')

    <!-- View Switcher & Date Navigation -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center p-1 bg-gray-50 rounded-xl border border-gray-100">
            <a href="{{ route('reservations.index', ['view' => 'calendar', 'month' => $date->format('Y-m')]) }}"
               class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $view === 'calendar' ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-calendar-alt mr-2"></i>Vista Calendario
            </a>
            <a href="{{ route('reservations.index', ['view' => 'list', 'month' => $date->format('Y-m')]) }}"
               class="flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 {{ $view === 'list' ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-list mr-2"></i>Vista Lista
            </a>
        </div>

        @if($view === 'calendar')
        <div class="flex items-center justify-between md:justify-end gap-4 min-w-[300px]">
            <a href="{{ route('reservations.index', ['view' => 'calendar', 'month' => $date->copy()->subMonth()->format('Y-m')]) }}"
               class="p-2.5 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl text-gray-500 transition-all border border-transparent hover:border-emerald-100">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="text-center min-w-[150px]">
                <h2 class="text-lg font-bold text-gray-900 capitalize tracking-tight">
                    {{ ucfirst($date->translatedFormat('F Y')) }}
                </h2>
            </div>
            <a href="{{ route('reservations.index', ['view' => 'calendar', 'month' => $date->copy()->addMonth()->format('Y-m')]) }}"
               class="p-2.5 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl text-gray-500 transition-all border border-transparent hover:border-emerald-100">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        @endif
    </div>

    @if($view === 'calendar')
    <!-- Legend -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center text-xs font-bold text-gray-600">
                <span class="w-4 h-4 rounded-md bg-emerald-500 mr-2.5 shadow-sm"></span> LIBRE
            </div>
            <div class="flex items-center text-xs font-bold text-gray-600">
                <span class="w-4 h-4 rounded-md bg-blue-500 mr-2.5 shadow-sm"></span> RESERVADA
            </div>
            <div class="flex items-center text-xs font-bold text-gray-600">
                <span class="w-4 h-4 rounded-md bg-red-500 mr-2.5 shadow-sm"></span> OCUPADA
            </div>
            <div class="flex items-center text-xs font-bold text-gray-600">
                <span class="w-4 h-4 rounded-md bg-yellow-400 mr-2.5 shadow-sm"></span> MANTENIMIENTO
            </div>
            <div class="flex items-center text-xs font-bold text-gray-600">
                <span class="w-4 h-4 rounded-md bg-[#6F4E37] mr-2.5 shadow-sm"></span> LIMPIEZA
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-lg overflow-hidden">
        <div class="overflow-x-auto overflow-y-hidden">
            <table class="min-w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-20 bg-gray-50 px-4 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-r min-w-[100px] shadow-[4px_0_8px_-4px_rgba(0,0,0,0.05)]">
                            <div class="flex items-center">
                                <i class="fas fa-door-open mr-2 text-emerald-500/50"></i>
                                Hab.
                            </div>
                        </th>
                        @foreach($daysInMonth as $day)
                        <th class="px-1 py-3 text-center border-b border-r w-[45px] min-w-[45px] {{ $day->isToday() ? 'bg-emerald-50 ring-2 ring-emerald-500 ring-inset z-10' : 'bg-gray-50' }}">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-tighter">{{ substr($day->translatedFormat('D'), 0, 1) }}</span>
                            <span class="text-sm font-black {{ $day->isToday() ? 'text-emerald-700' : 'text-gray-700' }}">{{ $day->day }}</span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rooms as $room)
                    <tr class="hover:bg-gray-50/50 transition-colors h-14">
                        <td class="sticky left-0 z-20 bg-white px-4 py-3 border-r shadow-[4px_0_8px_-4px_rgba(0,0,0,0.05)] min-w-[100px]">
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-black text-gray-900 tracking-tighter">{{ $room->room_number }}</span>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">{{ $room->beds_count }} {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }}</span>
                            </div>
                        </td>
                        @foreach($daysInMonth as $day)
                            @php
                                $status = 'free';
                                $reservation = $room->reservations->first(function($res) use ($day) {
                                    if (!$res->check_in_date || !$res->check_out_date) {
                                        return false;
                                    }
                                    $checkIn = \Carbon\Carbon::parse($res->check_in_date);
                                    $checkOut = \Carbon\Carbon::parse($res->check_out_date);
                                    return $day->isBetween($checkIn, $checkOut->copy()->subDay());
                                });

                                if ($reservation) {
                                    // Cualquier día con reserva se muestra como OCUPADO (Rojo) para control total
                                    $status = 'occupied';
                                } elseif ($room->status->value === 'mantenimiento') {
                                    $status = 'maintenance';
                                } elseif ($room->status->value === 'limpieza') {
                                    $status = 'cleaning';
                                }

                                $colorClass = [
                                    'free' => 'bg-emerald-500 hover:bg-emerald-600',
                                    'reserved' => 'bg-blue-500 hover:bg-blue-600',
                                    'occupied' => 'bg-red-500 hover:bg-red-600',
                                    'maintenance' => 'bg-yellow-400 hover:bg-yellow-500',
                                    'cleaning' => 'bg-[#6F4E37] hover:bg-[#5D4037]'
                                ][$status];
                            @endphp
                            <td class="p-1 border-r border-b relative group w-[45px] min-w-[45px]">
                                <div class="w-full h-10 rounded-lg {{ $colorClass }} cursor-pointer transition-all duration-200 flex items-center justify-center overflow-hidden shadow-sm"
                                     @if($reservation && $reservation->customer)
                                     onclick="openReservationDetail({{ json_encode([
                                         'id' => $reservation->id,
                                         'customer_name' => $reservation->customer ? $reservation->customer->name : 'Cliente eliminado',
                                         'room_number' => $room->room_number,
                                         'beds_count' => $room->beds_count . ($room->beds_count == 1 ? ' Cama' : ' Camas'),
                                         'check_in' => $reservation->check_in_date ? $reservation->check_in_date->format('d/m/Y') : 'N/A',
                                         'check_out' => $reservation->check_out_date ? $reservation->check_out_date->format('d/m/Y') : 'N/A',
                                         'check_in_time' => $reservation->check_in_time ? substr((string) $reservation->check_in_time, 0, 5) : 'N/A',
                                         'guests_count' => (int) ($reservation->guests_count ?? 0),
                                         'payment_method' => $reservation->payment_method ? (string) $reservation->payment_method : 'N/A',
                                         'total' => number_format($reservation->total_amount, 0, ',', '.'),
                                         'deposit' => number_format($reservation->deposit, 0, ',', '.'),
                                         'balance' => number_format($reservation->total_amount - $reservation->deposit, 0, ',', '.'),
                                         'edit_url' => route('reservations.edit', $reservation),
                                         'pdf_url' => route('reservations.download', $reservation),
                                         'notes' => $reservation->notes ?? 'Sin notas adicionales'
                                     ]) }})"
                                     @endif>
                                    @if($reservation)
                                        <i class="fas fa-eye text-white text-[10px] opacity-0 group-hover:opacity-100 transition-opacity transform group-hover:scale-125"></i>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($view === 'list')
    <!-- Tabla de reservas - Desktop -->
    <div class="hidden lg:block bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto -mx-6 lg:mx-0">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Habitación</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Entrada / Salida</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total / Abono</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($reservations as $reservation)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-semibold">
                                    {{ $reservation->customer ? strtoupper(substr($reservation->customer->name, 0, 1)) : '?' }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-semibold text-gray-900">{{ $reservation->customer ? $reservation->customer->name : 'Cliente eliminado' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @if($reservation->room)
                                <span class="font-semibold">{{ $reservation->room->room_number }}</span>
                                <span class="text-xs text-gray-500 block">{{ $reservation->room->beds_count }} {{ $reservation->room->beds_count == 1 ? 'Cama' : 'Camas' }}</span>
                            @else
                                <span class="text-gray-400 italic">Habitación eliminada</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <div><i class="fas fa-sign-in-alt text-emerald-500 mr-2"></i>{{ $reservation->check_in_date ? $reservation->check_in_date->format('d/m/Y') : 'N/A' }}</div>
                            <div><i class="fas fa-sign-out-alt text-red-500 mr-2"></i>{{ $reservation->check_out_date ? $reservation->check_out_date->format('d/m/Y') : 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex flex-col space-y-1 min-w-[120px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total:</span>
                                    <span class="font-bold text-gray-900">${{ number_format($reservation->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-400">Abono:</span>
                                    <span class="text-emerald-600 font-semibold">${{ number_format($reservation->deposit, 0, ',', '.') }}</span>
                                </div>
                                <div class="pt-1 mt-1 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-gray-500 text-[10px] uppercase font-bold">Saldo:</span>
                                    @php
                                        $balance = $reservation->total_amount - $reservation->deposit;
                                    @endphp
                                    @if($balance > 0)
                                        <span class="text-xs text-red-600 font-bold bg-red-50 px-1.5 py-0.5 rounded">${{ number_format($balance, 0, ',', '.') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">
                                            <i class="fas fa-check-circle mr-1"></i> Pagado
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('reservations.download', $reservation) }}"
                                   class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                   title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <a href="{{ route('reservations.edit', $reservation) }}"
                                   class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button"
                                        onclick="openDeleteModal({{ $reservation->id }})"
                                        class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                        title="Cancelar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">No hay reservas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reservations->hasPages())
        <div class="bg-white px-6 py-4 border-t border-gray-100">
            {{ $reservations->links() }}
        </div>
        @endif
    </div>
    @endif
</div>

<!-- Modal de Detalle de Reserva -->
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

<!-- Modal de Cancelación -->
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

@push('scripts')
<script>
function openReservationDetail(data) {
    const modal = document.getElementById('reservation-detail-modal');

    document.getElementById('modal-customer-name').innerText = data.customer_name;
    document.getElementById('modal-reservation-id').innerText = 'Reserva #' + data.id;
    document.getElementById('modal-room-info').innerText = 'Hab. ' + data.room_number + ' (' + data.beds_count + ')';
    document.getElementById('modal-dates').innerText = data.check_in + ' - ' + data.check_out;
    document.getElementById('modal-checkin-time').innerText = data.check_in_time;
    document.getElementById('modal-guests-count').innerText = data.guests_count;
    document.getElementById('modal-payment-method').innerText = data.payment_method;
    document.getElementById('modal-total').innerText = '$' + data.total;
    document.getElementById('modal-deposit').innerText = '$' + data.deposit;
    document.getElementById('modal-balance').innerText = '$' + data.balance;
    document.getElementById('modal-notes').innerText = data.notes;

    document.getElementById('modal-edit-btn').href = data.edit_url;
    document.getElementById('modal-pdf-btn').href = data.pdf_url;
    document.getElementById('modal-delete-btn').onclick = () => {
        closeReservationDetail();
        openDeleteModal(data.id);
    };

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReservationDetail() {
    document.getElementById('reservation-detail-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openDeleteModal(id) {
    const modal = document.getElementById('delete-modal');
    const form = document.getElementById('delete-form');
    form.action = '{{ route("reservations.destroy", ":id") }}'.replace(':id', id);
    modal.classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}

function confirmDeleteWithPin(form) {
    form.submit();
}
</script>
@endpush
@endsection

