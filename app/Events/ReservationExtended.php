<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationExtended
{
    use Dispatchable, SerializesModels;

    public int $roomId;
    public string $roomNumber;

    public function __construct(int $roomId, string $roomNumber)
    {
        $this->roomId = $roomId;
        $this->roomNumber = $roomNumber;
    }
}
