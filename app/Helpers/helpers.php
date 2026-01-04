<?php

if (!function_exists('settings')) {
    function settings() {
        $settings = cache()->remember('settings', 24*60, function () {
            //return \Modules\Setting\Entities\Setting::firstOrFail();
        });

        return $settings;
    }
}

if (!function_exists('format_currency')) {
    function format_currency($value, $format = true) {
        if (!$format) {
            return $value;
        }

        $settings = settings();
        $position = $settings->default_currency_position ?? '2';
        $symbol = $settings->currency->symbol ?? ' $';
        $decimal_separator = $settings->currency->decimal_separator ?? '.';
        $thousand_separator = $settings->currency->thousand_separator ?? ',';

        if ($position == 'prefix') {
            $formatted_value = $symbol . number_format((float) $value, 2, $decimal_separator, $thousand_separator);
        } else {
            $formatted_value = number_format((float) $value, 2, $decimal_separator, $thousand_separator) . $symbol;
        }

        return $formatted_value;
    }
}

if (!function_exists('make_reference_id')) {
    function make_reference_id($prefix, $number) {
        $padded_text = $prefix . '-' . str_pad($number, 5, 0, STR_PAD_LEFT);

        return $padded_text;
    }
}

if (!function_exists('array_merge_numeric_values')) {
    function array_merge_numeric_values() {
        $arrays = func_get_args();
        $merged = array();
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                if (!isset($merged[$key])) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] += $value;
                }
            }
        }

        return $merged;
    }
}

if (!function_exists('hotel_check_in_time')) {
    /**
     * Get the configured check-in time
     * 
     * @return string Time in H:i format (e.g., "15:00")
     */
    function hotel_check_in_time() {
        return config('hotel.check_in_time', '15:00');
    }
}

if (!function_exists('hotel_check_out_time')) {
    /**
     * Get the configured check-out time
     * 
     * @return string Time in H:i format (e.g., "12:00")
     */
    function hotel_check_out_time() {
        return config('hotel.check_out_time', '12:00');
    }
}

if (!function_exists('hotel_early_check_in_time')) {
    /**
     * Get the configured early check-in time
     * 
     * @return string Time in H:i format (e.g., "12:00")
     */
    function hotel_early_check_in_time() {
        return config('hotel.early_check_in_time', '12:00');
    }
}

if (!function_exists('hotel_late_check_out_time')) {
    /**
     * Get the configured late check-out time
     * 
     * @return string Time in H:i format (e.g., "15:00")
     */
    function hotel_late_check_out_time() {
        return config('hotel.late_check_out_time', '15:00');
    }
}

if (!function_exists('hotel_check_out_grace_period')) {
    /**
     * Get the configured grace period after check-out time (in minutes)
     * 
     * @return int Minutes
     */
    function hotel_check_out_grace_period() {
        return config('hotel.check_out_grace_period_minutes', 30);
    }
}

if (!function_exists('hotel_minimum_rental_hours')) {
    /**
     * Get the minimum rental hours
     * 
     * @return int Hours
     */
    function hotel_minimum_rental_hours() {
        return config('hotel.minimum_rental_hours', 24);
    }
}
