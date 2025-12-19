<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel de Aseo - Hotel San Pedro</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Smooth transitions */
        .room-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .room-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-weight: 700;
            letter-spacing: 0.025em;
        }
        
        .action-button {
            transition: all 0.2s ease-in-out;
            font-weight: 700;
            letter-spacing: 0.025em;
        }
        
        .action-button:active {
            transform: scale(0.98);
        }
        
        @keyframes pulse-success {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .pulse-success {
            animation: pulse-success 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen" x-data="roomStatusApp()" x-cloak>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-7xl">
        <!-- Header - More robust -->
        <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-200 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md">
                        <i class="fas fa-broom text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-black text-gray-900 leading-tight">Panel de Aseo</h1>
                        <p class="text-base sm:text-lg text-gray-600 mt-1 font-medium">Sistema de Gestión de Limpieza</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3 bg-gray-50 px-4 py-2 rounded-xl border border-gray-200">
                    <i class="fas fa-clock text-gray-600"></i>
                    <span class="text-base font-bold text-gray-700" x-text="currentTime"></span>
                </div>
            </div>
        </div>

        <!-- Status Legend - More robust -->
        <div class="bg-white rounded-2xl shadow-md border-2 border-gray-200 p-5 mb-6">
            <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-4">Leyenda de Estados</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="flex items-center space-x-3 bg-emerald-50 px-4 py-3 rounded-xl border-2 border-emerald-300">
                    <div class="w-5 h-5 rounded-full bg-emerald-600 shadow-sm"></div>
                    <span class="text-sm font-bold text-emerald-800">Libre</span>
                </div>
                <div class="flex items-center space-x-3 bg-blue-50 px-4 py-3 rounded-xl border-2 border-blue-300">
                    <div class="w-5 h-5 rounded-full bg-blue-600 shadow-sm"></div>
                    <span class="text-sm font-bold text-blue-800">Ocupada</span>
                </div>
                <div class="flex items-center space-x-3 bg-red-50 px-4 py-3 rounded-xl border-2 border-red-300">
                    <div class="w-5 h-5 rounded-full bg-red-600 shadow-sm"></div>
                    <span class="text-sm font-bold text-red-800">Sucia</span>
                </div>
                <div class="flex items-center space-x-3 bg-purple-50 px-4 py-3 rounded-xl border-2 border-purple-300">
                    <div class="w-5 h-5 rounded-full bg-purple-600 shadow-sm"></div>
                    <span class="text-sm font-bold text-purple-800">Pendiente Checkout</span>
                </div>
            </div>
        </div>

        <!-- Rooms Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($rooms as $room)
                @php
                    $status = $room->display_status;
                    $statusConfig = match($status) {
                        \App\Enums\RoomStatus::LIBRE => [
                            'bg' => 'bg-emerald-50',
                            'border' => 'border-emerald-400',
                            'text' => 'text-emerald-800',
                            'badge' => 'bg-emerald-100 border-emerald-400 text-emerald-800',
                            'icon' => 'fa-check-circle',
                            'iconColor' => 'text-emerald-600',
                        ],
                        \App\Enums\RoomStatus::OCUPADA => [
                            'bg' => 'bg-blue-50',
                            'border' => 'border-blue-400',
                            'text' => 'text-blue-800',
                            'badge' => 'bg-blue-100 border-blue-400 text-blue-800',
                            'icon' => 'fa-user',
                            'iconColor' => 'text-blue-600',
                        ],
                        \App\Enums\RoomStatus::SUCIA => [
                            'bg' => 'bg-red-50',
                            'border' => 'border-red-500',
                            'text' => 'text-red-800',
                            'badge' => 'bg-red-100 border-red-500 text-red-800',
                            'icon' => 'fa-broom',
                            'iconColor' => 'text-red-600',
                        ],
                        \App\Enums\RoomStatus::LIMPIEZA => [
                            'bg' => 'bg-red-50',
                            'border' => 'border-red-500',
                            'text' => 'text-red-800',
                            'badge' => 'bg-red-100 border-red-500 text-red-800',
                            'icon' => 'fa-broom',
                            'iconColor' => 'text-red-600',
                        ],
                        \App\Enums\RoomStatus::PENDIENTE_CHECKOUT => [
                            'bg' => 'bg-purple-50',
                            'border' => 'border-purple-400',
                            'text' => 'text-purple-800',
                            'badge' => 'bg-purple-100 border-purple-400 text-purple-800',
                            'icon' => 'fa-clock',
                            'iconColor' => 'text-purple-600',
                        ],
                        default => [
                            'bg' => 'bg-gray-50',
                            'border' => 'border-gray-300',
                            'text' => 'text-gray-700',
                            'badge' => 'bg-gray-100 border-gray-300 text-gray-700',
                            'icon' => 'fa-door-open',
                            'iconColor' => 'text-gray-600',
                        ],
                    };
                @endphp
                
                <div class="room-card bg-white rounded-2xl shadow-lg border-3 {{ $statusConfig['border'] }} {{ $statusConfig['bg'] }}">
                    <div class="p-5 sm:p-6">
                        <!-- Room Number - More prominent -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 rounded-xl {{ $statusConfig['badge'] }}">
                                    <i class="fas {{ $statusConfig['icon'] }} text-2xl {{ $statusConfig['iconColor'] }}"></i>
                                </div>
                                <h3 class="text-3xl sm:text-4xl font-black {{ $statusConfig['text'] }}">#{{ $room->room_number }}</h3>
                            </div>
                        </div>

                        <!-- Status Badge - More robust -->
                        <div class="mb-5">
                            <span class="status-badge inline-flex items-center px-4 py-2 rounded-xl text-sm {{ $statusConfig['badge'] }} border-2">
                                <i class="fas {{ $statusConfig['icon'] }} mr-2"></i>
                                {{ $status->label() }}
                            </span>
                        </div>

                        <!-- Room Info - Clearer -->
                        <div class="space-y-3 mb-5">
                            <div class="flex items-center space-x-3 text-base font-semibold {{ $statusConfig['text'] }}">
                                <i class="fas fa-bed {{ $statusConfig['iconColor'] }}"></i>
                                <span>{{ $room->beds_count }} {{ $room->beds_count === 1 ? 'cama' : 'camas' }}</span>
                            </div>
                            <div class="flex items-center space-x-3 text-base font-semibold {{ $statusConfig['text'] }}">
                                <i class="fas fa-users {{ $statusConfig['iconColor'] }}"></i>
                                <span>Capacidad: {{ $room->max_capacity }} personas</span>
                            </div>
                        </div>

                        <!-- Action Button - Large, green, with icon -->
                        @if($room->can_mark_clean)
                            <button 
                                @click="markAsClean({{ $room->id }}, '{{ $room->room_number }}')"
                                :disabled="loading"
                                class="action-button w-full py-4 px-6 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl transition-all duration-200 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 text-base"
                                :class="{
                                    'opacity-50 cursor-not-allowed': loading && currentRoomId === {{ $room->id }},
                                    'pulse-success': successRoomId === {{ $room->id }}
                                }">
                                <i class="fas text-lg" 
                                   :class="{
                                       'fa-check-circle': !loading || currentRoomId !== {{ $room->id }},
                                       'fa-spinner fa-spin': loading && currentRoomId === {{ $room->id }}
                                   }"></i>
                                <span x-show="loading && currentRoomId === {{ $room->id }}">Procesando...</span>
                                <span x-show="!loading || currentRoomId !== {{ $room->id }}">Marcar como Limpia</span>
                            </button>
                        @else
                            <div class="w-full py-4 px-6 bg-gray-100 border-2 border-gray-300 text-gray-600 font-bold rounded-xl text-center text-sm">
                                @if($status === \App\Enums\RoomStatus::OCUPADA)
                                    <i class="fas fa-user-slash mr-2"></i>Habitación ocupada
                                @elseif($status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT)
                                    <i class="fas fa-clock mr-2"></i>Pendiente checkout
                                @elseif($status === \App\Enums\RoomStatus::LIBRE)
                                    <i class="fas fa-check-circle mr-2"></i>Habitación disponible
                                @else
                                    <i class="fas fa-ban mr-2"></i>No disponible
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($rooms->isEmpty())
            <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-200 p-12 text-center">
                <div class="mb-6">
                    <i class="fas fa-door-open text-7xl text-gray-300"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-700 mb-2">No hay habitaciones registradas</h3>
                <p class="text-gray-500 font-medium">Contacte al administrador del sistema.</p>
            </div>
        @endif
    </div>

    <script>
        function roomStatusApp() {
            return {
                loading: false,
                currentRoomId: null,
                successRoomId: null,
                currentTime: new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),

                init() {
                    // Update time every minute
                    setInterval(() => {
                        this.currentTime = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                    }, 60000);

                    // Auto-refresh every 30 seconds (only if not loading)
                    setInterval(() => {
                        if (!this.loading) {
                            window.location.reload();
                        }
                    }, 30000);
                },

                async markAsClean(roomId, roomNumber) {
                    if (this.loading) return;

                    // Confirm action with better UX
                    const result = await Swal.fire({
                        title: '<strong>¿Marcar como limpia?</strong>',
                        html: `<div class="text-left">
                            <p class="mb-3">¿Confirma que la habitación <strong>#${roomNumber}</strong> está completamente limpia y lista para arrendar?</p>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-3">
                                <p class="text-sm text-yellow-700"><i class="fas fa-exclamation-triangle mr-2"></i>Esta acción cambiará el estado a <strong>LIBRE</strong>.</p>
                            </div>
                        </div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#16a34a',
                        cancelButtonColor: '#dc2626',
                        confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, marcar como limpia',
                        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
                        reverseButtons: true,
                        buttonsStyling: true,
                        customClass: {
                            confirmButton: 'px-6 py-3 text-base font-bold',
                            cancelButton: 'px-6 py-3 text-base font-bold'
                        }
                    });

                    if (!result.isConfirmed) return;

                    this.loading = true;
                    this.currentRoomId = roomId;
                    this.successRoomId = null;

                    try {
                        const response = await fetch(`/api/panel-aseo/rooms/${roomId}/clean`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });

                        // API always returns JSON
                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.successRoomId = roomId;
                            
                            await Swal.fire({
                                title: '<strong>¡Éxito!</strong>',
                                html: `<div class="text-left">
                                    <p class="mb-2 font-semibold">${data.message}</p>
                                    <div class="bg-green-50 border-l-4 border-green-400 p-3 mt-3 rounded-r">
                                        <p class="text-sm text-green-700 font-medium"><i class="fas fa-check-circle mr-2"></i>La habitación ahora está disponible para arrendar.</p>
                                    </div>
                                </div>`,
                                icon: 'success',
                                confirmButtonColor: '#16a34a',
                                confirmButtonText: '<i class="fas fa-check mr-2"></i>Aceptar',
                                timer: 3000,
                                timerProgressBar: true,
                                customClass: {
                                    confirmButton: 'px-6 py-3 text-base font-bold rounded-xl'
                                }
                            });

                            // Reload page after short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        } else {
                            // Handle validation errors (403) and other errors
                            const errorMessage = data.message || 'No se pudo procesar la solicitud.';
                            const errorReason = data.reason || 'unknown';
                            
                            let errorDetail = '';
                            if (errorReason === 'occupied') {
                                errorDetail = '<p class="text-sm text-red-700 font-medium"><i class="fas fa-user-slash mr-2"></i>La habitación está actualmente ocupada por un huésped.</p>';
                            } else if (errorReason === 'pending_checkout') {
                                errorDetail = '<p class="text-sm text-red-700 font-medium"><i class="fas fa-clock mr-2"></i>Esperando confirmación de checkout del huésped.</p>';
                            } else if (errorReason === 'reservation_starts_today') {
                                errorDetail = '<p class="text-sm text-red-700 font-medium"><i class="fas fa-calendar-check mr-2"></i>Hay una reserva programada para hoy.</p>';
                            } else {
                                errorDetail = '<p class="text-sm text-red-700 font-medium"><i class="fas fa-exclamation-circle mr-2"></i>Por favor, verifique el estado de la habitación.</p>';
                            }
                            
                            await Swal.fire({
                                title: '<strong>Error</strong>',
                                html: `<div class="text-left">
                                    <p class="mb-2 font-semibold">${errorMessage}</p>
                                    <div class="bg-red-50 border-l-4 border-red-400 p-3 mt-3 rounded-r">
                                        ${errorDetail}
                                    </div>
                                </div>`,
                                icon: 'error',
                                confirmButtonColor: '#dc2626',
                                confirmButtonText: '<i class="fas fa-times mr-2"></i>Entendido',
                                customClass: {
                                    confirmButton: 'px-6 py-3 text-base font-bold rounded-xl'
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        
                        await Swal.fire({
                            title: '<strong>Error de conexión</strong>',
                            html: `<div class="text-left">
                                <p>${error.message || 'No se pudo conectar con el servidor.'}</p>
                                <div class="bg-red-50 border-l-4 border-red-400 p-3 mt-3">
                                    <p class="text-sm text-red-700"><i class="fas fa-exclamation-circle mr-2"></i>Por favor, intente nuevamente.</p>
                                </div>
                            </div>`,
                            icon: 'error',
                            confirmButtonColor: '#dc2626',
                            confirmButtonText: 'Entendido'
                        });
                    } finally {
                        this.loading = false;
                        this.currentRoomId = null;
                        // Clear success state after animation
                        setTimeout(() => {
                            this.successRoomId = null;
                        }, 2000);
                    }
                }
            }
        }
    </script>
</body>
</html>
