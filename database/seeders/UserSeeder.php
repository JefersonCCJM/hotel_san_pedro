<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        // Usuario administrador (safe - won't duplicate if exists)
        $adminData = [
            'name' => 'Administrador',
            'password' => Hash::make('Brandon-Administrador-2025#'),
        ];

        if ($hasUsernameColumn) {
            $adminData['username'] = 'admin';
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@moviltech.com'],
            $adminData
        );
        
        if (!$admin->hasRole('Administrador')) {
            $admin->assignRole('Administrador');
        }

        // Usuario recepcionista día (alineado al dominio hotelero)
        $receptionistData = [
            'name' => 'Recepcionista Día',
            'password' => Hash::make('Recepcionista2025#'),
        ];

        if ($hasUsernameColumn) {
            $receptionistData['username'] = 'recepcionista.dia';
        }

        $receptionist = User::firstOrCreate(
            ['email' => 'recepcionista.dia@hotel.com'],
            $receptionistData
        );
        
        if (!$receptionist->hasRole('Recepcionista Día')) {
            $receptionist->assignRole('Recepcionista Día');
        }
    }
}
