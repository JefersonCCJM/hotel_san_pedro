<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Customer;
use App\Models\Reservation;
use App\Enums\RoomStatus;
use Carbon\Carbon;

class SalesTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando datos de prueba para ventas...');

        // Crear habitaciones si no existen
        $this->createRooms();

        // Crear clientes de prueba
        $customers = $this->createCustomers();

        // Crear reservas activas
        $this->createReservations($customers);

        $this->command->info('✅ Datos de prueba creados exitosamente!');
        $this->command->info('   - Habitaciones: ' . Room::count());
        $this->command->info('   - Clientes: ' . Customer::count());
        $this->command->info('   - Reservas activas: ' . Reservation::where('check_out_date', '>=', Carbon::today())->count());
    }

    /**
     * Create test rooms if they don't exist.
     */
    private function createRooms(): void
    {
        $rooms = [
            [
                'room_number' => '101',
                'beds_count' => 1,
                'max_capacity' => 2,
                'price_per_night' => 50000,
                'price_1_person' => 50000,
                'price_2_persons' => 80000,
                'price_additional_person' => 20000,
                'status' => RoomStatus::OCUPADA,
            ],
            [
                'room_number' => '102',
                'beds_count' => 2,
                'max_capacity' => 4,
                'price_per_night' => 80000,
                'price_1_person' => 80000,
                'price_2_persons' => 120000,
                'price_additional_person' => 30000,
                'status' => RoomStatus::OCUPADA,
            ],
            [
                'room_number' => '103',
                'beds_count' => 1,
                'max_capacity' => 2,
                'price_per_night' => 50000,
                'price_1_person' => 50000,
                'price_2_persons' => 80000,
                'price_additional_person' => 20000,
                'status' => RoomStatus::OCUPADA,
            ],
            [
                'room_number' => '201',
                'beds_count' => 2,
                'max_capacity' => 4,
                'price_per_night' => 100000,
                'price_1_person' => 100000,
                'price_2_persons' => 150000,
                'price_additional_person' => 35000,
                'status' => RoomStatus::LIBRE,
            ],
            [
                'room_number' => '202',
                'beds_count' => 1,
                'max_capacity' => 2,
                'price_per_night' => 60000,
                'price_1_person' => 60000,
                'price_2_persons' => 90000,
                'price_additional_person' => 25000,
                'status' => RoomStatus::LIBRE,
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::firstOrCreate(
                ['room_number' => $roomData['room_number']],
                $roomData
            );
        }

        $this->command->info('   Habitaciones creadas/verificadas');
    }

    /**
     * Create test customers.
     */
    private function createCustomers(): array
    {
        $customers = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@example.com',
                'phone' => '3001234567',
                'address' => 'Calle 123 #45-67',
                'city' => 'Bogotá',
                'state' => 'Cundinamarca',
                'zip_code' => '110111',
                'is_active' => true,
                'requires_electronic_invoice' => false,
            ],
            [
                'name' => 'María González',
                'email' => 'maria.gonzalez@example.com',
                'phone' => '3002345678',
                'address' => 'Carrera 78 #90-12',
                'city' => 'Medellín',
                'state' => 'Antioquia',
                'zip_code' => '050001',
                'is_active' => true,
                'requires_electronic_invoice' => false,
            ],
            [
                'name' => 'Carlos Rodríguez',
                'email' => 'carlos.rodriguez@example.com',
                'phone' => '3003456789',
                'address' => 'Avenida 56 #78-90',
                'city' => 'Cali',
                'state' => 'Valle del Cauca',
                'zip_code' => '760001',
                'is_active' => true,
                'requires_electronic_invoice' => false,
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana.martinez@example.com',
                'phone' => '3004567890',
                'address' => 'Calle 34 #56-78',
                'city' => 'Barranquilla',
                'state' => 'Atlántico',
                'zip_code' => '080001',
                'is_active' => true,
                'requires_electronic_invoice' => false,
            ],
            [
                'name' => 'Luis Fernández',
                'email' => 'luis.fernandez@example.com',
                'phone' => '3005678901',
                'address' => 'Carrera 12 #34-56',
                'city' => 'Bogotá',
                'state' => 'Cundinamarca',
                'zip_code' => '110111',
                'is_active' => true,
                'requires_electronic_invoice' => false,
            ],
        ];

        $createdCustomers = [];
        foreach ($customers as $customerData) {
            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );
            $createdCustomers[] = $customer;
        }

        $this->command->info('   Clientes creados/verificados');
        return $createdCustomers;
    }

    /**
     * Create active reservations.
     */
    private function createReservations(array $customers): void
    {
        $rooms = Room::where('status', RoomStatus::OCUPADA)->get();

        if ($rooms->isEmpty()) {
            $this->command->warn('   No hay habitaciones ocupadas para crear reservas');
            return;
        }

        $today = Carbon::today();
        $reservations = [];

        // Reserva 1: Check-in ayer, check-out mañana
        if (isset($rooms[0]) && isset($customers[0])) {
            $checkIn = $today->copy()->subDay();
            $checkOut = $today->copy()->addDay();
            $days = $checkIn->diffInDays($checkOut);
            $totalAmount = $rooms[0]->price_per_night * $days;

            Reservation::firstOrCreate(
                [
                    'room_id' => $rooms[0]->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                ],
                [
                    'customer_id' => $customers[0]->id,
                    'guests_count' => 2,
                    'total_amount' => $totalAmount,
                    'deposit' => $totalAmount * 0.3,
                    'payment_method' => 'efectivo',
                    'reservation_date' => $checkIn->copy()->subDays(2),
                    'notes' => 'Reserva de prueba - Check-in ayer',
                ]
            );
        }

        // Reserva 2: Check-in hoy, check-out en 2 días
        if (isset($rooms[1]) && isset($customers[1])) {
            $checkIn = $today;
            $checkOut = $today->copy()->addDays(2);
            $days = $checkIn->diffInDays($checkOut);
            $totalAmount = $rooms[1]->price_per_night * $days;

            Reservation::firstOrCreate(
                [
                    'room_id' => $rooms[1]->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                ],
                [
                    'customer_id' => $customers[1]->id,
                    'guests_count' => 3,
                    'total_amount' => $totalAmount,
                    'deposit' => $totalAmount * 0.5,
                    'payment_method' => 'transferencia',
                    'reservation_date' => $checkIn->copy()->subDays(1),
                    'notes' => 'Reserva de prueba - Check-in hoy',
                ]
            );
        }

        // Reserva 3: Check-in hoy, check-out mañana
        if (isset($rooms[2]) && isset($customers[2])) {
            $checkIn = $today;
            $checkOut = $today->copy()->addDay();
            $days = $checkIn->diffInDays($checkOut);
            $totalAmount = $rooms[2]->price_per_night * $days;

            Reservation::firstOrCreate(
                [
                    'room_id' => $rooms[2]->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                ],
                [
                    'customer_id' => $customers[2]->id,
                    'guests_count' => 1,
                    'total_amount' => $totalAmount,
                    'deposit' => $totalAmount * 0.4,
                    'payment_method' => 'efectivo',
                    'reservation_date' => $checkIn->copy()->subDays(3),
                    'notes' => 'Reserva de prueba - Una noche',
                ]
            );
        }

        $this->command->info('   Reservas activas creadas/verificadas');
    }
}
