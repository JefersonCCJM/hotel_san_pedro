<?php

return [
    /**
     * Human-friendly aliases for audit events.
     * Keys MUST match the stored `audit_logs.event` values.
     */
    'event_aliases' => [
        // Auth / security
        'login' => 'Inicio de sesión',
        'failed_login' => 'Fallo de inicio de sesión',
        'permission_change' => 'Cambio de permisos',
        'role_changed' => 'Cambio de rol',
        'impersonation_start' => 'Inicio de impersonación',
        'impersonation_end' => 'Fin de impersonación',

        // Shifts
        'shift_start' => 'Inicio de turno',
        'shift_end' => 'Entrega de turno',
        'shift_receive' => 'Recepción de turno',

        // Cashbox
        'cash_out' => 'Retiro de caja (turno)',
        'cash_out_delete' => 'Eliminación retiro de caja (turno)',
        'cash_outflow_create' => 'Gasto de caja',
        'cash_outflow_delete' => 'Eliminación gasto de caja',

        // Sales
        'sale_create' => 'Nueva venta',
        'sale_update' => 'Actualización de venta',
        'sale_delete' => 'Eliminación de venta',

        // Inventory
        'inventory_movement' => 'Movimiento de inventario',
        'inventory_delete' => 'Eliminación de producto',

        // Reservations
        'reservation_deleted' => 'Eliminación de reservación',
    ],

    /**
     * Tailwind badge classes per event (optional).
     */
    'badge_classes' => [
        'failed_login' => 'bg-red-100 text-red-800',
        'login' => 'bg-emerald-100 text-emerald-800',
        'permission_change' => 'bg-amber-100 text-amber-800',
        'role_changed' => 'bg-amber-100 text-amber-800',
        'impersonation_start' => 'bg-indigo-100 text-indigo-800',
        'impersonation_end' => 'bg-indigo-100 text-indigo-800',

        'shift_start' => 'bg-blue-100 text-blue-800',
        'shift_end' => 'bg-amber-100 text-amber-800',
        'shift_receive' => 'bg-blue-100 text-blue-800',

        'cash_out' => 'bg-rose-100 text-rose-800',
        'cash_out_delete' => 'bg-rose-100 text-rose-800',
        'cash_outflow_create' => 'bg-rose-100 text-rose-800',
        'cash_outflow_delete' => 'bg-rose-100 text-rose-800',

        'sale_create' => 'bg-purple-100 text-purple-800',
        'sale_update' => 'bg-purple-100 text-purple-800',
        'sale_delete' => 'bg-purple-100 text-purple-800',

        'inventory_movement' => 'bg-gray-100 text-gray-800',
        'inventory_delete' => 'bg-gray-100 text-gray-800',

        'reservation_deleted' => 'bg-gray-100 text-gray-800',
    ],
];


