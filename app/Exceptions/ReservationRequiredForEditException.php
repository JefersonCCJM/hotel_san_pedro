<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ReservationRequiredForEditException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Reservation is required for editing.');
    }
}


