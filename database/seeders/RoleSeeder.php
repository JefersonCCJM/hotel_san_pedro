<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            // Inventario
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            
            // Clientes
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
            
            // Reservas
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            
            // Ventas
            'view_sales',
            'create_sales',
            'edit_sales',
            'delete_sales',
            
            // Facturación
            'generate_invoices',
            'download_invoices',
            
            // Turnos
            'view_shifts',
            'create_shifts',
            'edit_shifts',
            'delete_shifts',
            'view_shift_reports',
            
            // Reportes
            'view_reports',
            'export_reports',
            
            // Usuarios y roles
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles (safe - won't duplicate)
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $receptionistDayRole = Role::firstOrCreate(['name' => 'Recepcionista Día']);
        $receptionistNightRole = Role::firstOrCreate(['name' => 'Recepcionista Noche']);

        // Eliminar el rol Vendedor si existe (opcional, pero siguiendo "No se permiten más roles")
        Role::where('name', 'Vendedor')->delete();

        // Asignar permisos al administrador (todos)
        $adminRole->givePermissionTo(Permission::all());

        // Asignar permisos al Recepcionista Día
        $receptionistDayRole->givePermissionTo([
            'view_products',
            'view_categories',
            'view_customers',
            'create_customers',
            'edit_customers',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'view_sales',
            'create_sales',
            'edit_sales',
            'generate_invoices',
            'download_invoices',
            'view_shifts',
            'create_shifts',
            'view_reports',
        ]);

        // Asignar permisos al Recepcionista Noche
        $receptionistNightRole->givePermissionTo([
            'view_products',
            'view_categories',
            'view_customers',
            'create_customers',
            'edit_customers',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'view_sales',
            'create_sales',
            'edit_sales',
            'generate_invoices',
            'download_invoices',
            'view_shifts',
            'create_shifts',
            'view_reports',
        ]);
    }
}
