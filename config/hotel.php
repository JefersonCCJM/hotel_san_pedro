<?php

return [
    /**
     * Check-in time configuration
     * Default time when guests can check-in
     */
    'check_in_time' => env('HOTEL_CHECK_IN_TIME', '15:00'),

    /**
     * Check-out time configuration
     * Default time when guests must check-out
     */
    'check_out_time' => env('HOTEL_CHECK_OUT_TIME', '12:00'),

    /**
     * Early check-in time (optional)
     * Time from which early check-in is available
     */
    'early_check_in_time' => env('HOTEL_EARLY_CHECK_IN_TIME', '12:00'),

    /**
     * Late check-out time (optional)
     * Time until which late check-out is available
     */
    'late_check_out_time' => env('HOTEL_LATE_CHECK_OUT_TIME', '15:00'),

    /**
     * Grace period (in minutes) after check-out time
     * Time buffer before marking a room as overdue
     */
    'check_out_grace_period_minutes' => env('HOTEL_CHECK_OUT_GRACE_PERIOD', 30),

    /**
     * Minimum rental hours
     * Minimum hours a room can be rented for
     */
    'minimum_rental_hours' => env('HOTEL_MIN_RENTAL_HOURS', 24),

    /**
     * Timezone configuration
     */
    'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
];
