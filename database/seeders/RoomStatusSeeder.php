<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomStatus;

class RoomStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Libre',
                'code' => 'libre',
                'color' => 'bg-emerald-50 text-emerald-700',
                'icon' => 'fa-check-circle',
                'is_visible_public' => true,
                'is_actionable' => false,
                'description' => 'Habitación disponible para arrendar',
                'order' => 1,
            ],
            [
                'name' => 'Reservada',
                'code' => 'reservada',
                'color' => 'bg-blue-50 text-blue-700',
                'icon' => 'fa-calendar-check',
                'is_visible_public' => true,
                'is_actionable' => false,
                'description' => 'Habitación reservada para fechas futuras',
                'order' => 2,
            ],
            [
                'name' => 'Ocupada',
                'code' => 'ocupada',
                'color' => 'bg-blue-50 text-blue-700',
                'icon' => 'fa-user',
                'is_visible_public' => true,
                'is_actionable' => false,
                'description' => 'Habitación ocupada por huésped activo',
                'order' => 3,
            ],
            [
                'name' => 'Sucia',
                'code' => 'sucia',
                'color' => 'bg-red-50 text-red-700',
                'icon' => 'fa-broom',
                'is_visible_public' => true,
                'is_actionable' => true,
                'description' => 'Habitación requiere limpieza',
                'order' => 4,
            ],
            [
                'name' => 'Limpieza',
                'code' => 'limpieza',
                'color' => 'bg-orange-50 text-orange-700',
                'icon' => 'fa-broom',
                'is_visible_public' => true,
                'is_actionable' => true,
                'description' => 'Habitación en proceso de limpieza',
                'order' => 5,
            ],
            [
                'name' => 'Mantenimiento',
                'code' => 'mantenimiento',
                'color' => 'bg-amber-50 text-amber-700',
                'icon' => 'fa-tools',
                'is_visible_public' => true,
                'is_actionable' => false,
                'description' => 'Habitación en mantenimiento',
                'order' => 6,
            ],
            [
                'name' => 'Pendiente Checkout',
                'code' => 'pendiente_checkout',
                'color' => 'bg-purple-50 text-purple-700',
                'icon' => 'fa-clock',
                'is_visible_public' => true,
                'is_actionable' => false,
                'description' => 'Esperando confirmación de checkout del huésped',
                'order' => 7,
            ],
        ];

        foreach ($statuses as $index => $status) {
            $roomStatus = RoomStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );

            if ($status['code'] === 'sucia' || $status['code'] === 'limpieza') {
                $libreStatus = RoomStatus::where('code', 'libre')->first();
                if ($libreStatus) {
                    $roomStatus->update(['next_status_id' => $libreStatus->id]);
                }
            }
        }
    }
}
