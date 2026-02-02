<?php

require_once 'vendor/autoload.php';

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar clientes
echo "=== VERIFICACIÓN DE CLIENTES ===\n";

$customers = \App\Models\Customer::all();
echo "Total clientes: " . $customers->count() . "\n\n";

if ($customers->count() > 0) {
    echo "Clientes encontrados:\n";
    foreach ($customers->take(5) as $customer) {
        echo "- ID: {$customer->id}, Nombre: {$customer->name}, Identificación: " . ($customer->identification_number ?? 'N/A') . "\n";
    }
} else {
    echo "❌ No hay clientes en la base de datos\n";
}

echo "\n=== VERIFICACIÓN DE RESERVAS ===\n";

$reservations = \App\Models\Reservation::all();
echo "Total reservas: " . $reservations->count() . "\n\n";

if ($reservations->count() > 0) {
    echo "Reservas encontradas:\n";
    foreach ($reservations->take(5) as $reservation) {
        echo "- Reserva ID: {$reservation->id}\n";
        echo "  Customer ID: " . ($reservation->customer_id ?? 'NULL') . "\n";
        echo "  Customer: " . ($reservation->customer ? $reservation->customer->name : 'NO ASOCIADO') . "\n";
        echo "  Total: {$reservation->total_amount}\n";
        echo "  Estado: " . ($reservation->status ?? 'SIN ESTADO') . "\n";
        echo "  Reservation Rooms: " . $reservation->reservationRooms->count() . "\n\n";
    }
} else {
    echo "❌ No hay reservas en la base de datos\n";
}

echo "\n=== VERIFICACIÓN DE RESERVATION_ROOMS ===\n";

$reservationRooms = \App\Models\ReservationRoom::all();
echo "Total reservation_rooms: " . $reservationRooms->count() . "\n\n";

if ($reservationRooms->count() > 0) {
    echo "Reservation Rooms encontrados:\n";
    foreach ($reservationRooms->take(5) as $rr) {
        echo "- RR ID: {$rr->id}\n";
        echo "  Reservation ID: {$rr->reservation_id}\n";
        echo "  Room ID: {$rr->room_id}\n";
        echo "  Check-in: {$rr->check_in_date}\n";
        echo "  Check-out: {$rr->check_out_date}\n";
        echo "  Reserva: " . ($rr->reservation ? "ID {$rr->reservation->id}" : 'NO ASOCIADA') . "\n";
        echo "  Cliente: " . ($rr->reservation && $rr->reservation->customer ? $rr->reservation->customer->name : 'NO ASOCIADO') . "\n\n";
    }
} else {
    echo "❌ No hay reservation_rooms en la base de datos\n";
}

echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
