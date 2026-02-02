<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Horarios globales del hotel
    |--------------------------------------------------------------------------
    |
    | Fuente única de verdad para todos los horarios del hotel.
    | Estos valores se usan en todo el sistema para mantener consistencia.
    |
    */

    'check_in_time' => env('HOTEL_CHECK_IN_TIME', '15:00'),   // Hora estándar de entrada
    'check_out_time' => env('HOTEL_CHECK_OUT_TIME', '12:00'),  // Hora estándar de salida

    /*
    |--------------------------------------------------------------------------
    | Reglas operativas
    |--------------------------------------------------------------------------
    |
    | Reglas adicionales que definen el comportamiento operativo del hotel.
    |
    */

    'early_check_in_time' => env('HOTEL_EARLY_CHECK_IN_TIME', '12:00'), // A partir de aquí es check-in temprano
    'late_check_out_time' => env('HOTEL_LATE_CHECK_OUT_TIME', '15:00'), // A partir de aquí es checkout tardío
    'cleaning_buffer_minutes' => env('HOTEL_CLEANING_BUFFER', 120),     // Tiempo promedio de limpieza (2 horas)
    'check_out_grace_period_minutes' => env('HOTEL_CHECK_OUT_GRACE_PERIOD', 30), // Período de gracia después de checkout

    /*
    |--------------------------------------------------------------------------
    | Reglas de negocio
    |--------------------------------------------------------------------------
    |
    | Configuraciones adicionales para el funcionamiento del hotel.
    |
    */

    'minimum_rental_hours' => env('HOTEL_MIN_RENTAL_HOURS', 24), // Horas mínimas de alquiler

    /*
    |--------------------------------------------------------------------------
    | Configuración de zona horaria
    |--------------------------------------------------------------------------
    |
    | Zona horaria del hotel para todos los cálculos de tiempo.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
];
