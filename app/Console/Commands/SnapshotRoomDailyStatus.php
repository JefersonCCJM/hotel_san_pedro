<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\RoomDailyStatus;
use App\Enums\RoomStatus;

class SnapshotRoomDailyStatus extends Command
{
    protected $signature = 'rooms:snapshot {date : Fecha (YYYY-MM-DD) a registrar}';

    protected $description = 'Genera snapshot diario de estados de habitaciones para la fecha indicada.';

    public function handle(): int
    {
        $dateInput = $this->argument('date');

        try {
            $snapshotDate = Carbon::parse($dateInput)->startOfDay();
        } catch (\Throwable $e) {
            $this->error('Fecha inválida, usa formato YYYY-MM-DD.');
            return Command::FAILURE;
        }

        $today = Carbon::today();
        if ($snapshotDate->gt($today)) {
            $this->error('No se permiten snapshots de fechas futuras.');
            return Command::FAILURE;
        }

        Room::chunkById(100, function ($rooms) use ($snapshotDate) {
            foreach ($rooms as $room) {
                // Capture the DISPLAY status - this is what the user sees in the interface
                // This includes "Ocupada" when there's a reservation, even if status field is "Libre"
                // This preserves exactly how the room appeared at the end of the day
                $displayStatus = $room->getDisplayStatus($snapshotDate);
                
                // Capture the cleaning status at the end of the day
                $cleaning = $room->cleaningStatus($snapshotDate);

                // Get reservation info if exists (for historical reference)
                $reservation = $room->reservations()
                    ->where('check_in_date', '<=', $snapshotDate)
                    ->where('check_out_date', '>', $snapshotDate)
                    ->with(['customer.taxProfile', 'guests.taxProfile'])
                    ->first();

                // If no active reservation, check for pending checkout
                if (!$reservation) {
                    $reservation = $room->getPendingCheckoutReservation($snapshotDate);
                    // Load guests if reservation was found
                    if ($reservation && !$reservation->relationLoaded('guests')) {
                        $reservation->load(['customer.taxProfile', 'guests.taxProfile']);
                    }
                }

                // Prepare guests data for snapshot (immutable record)
                $guestsData = null;
                if ($reservation) {
                    $mainGuest = [
                        'id' => $reservation->customer->id,
                        'name' => $reservation->customer->name,
                        'identification' => $reservation->customer->taxProfile?->identification ?? null,
                        'phone' => $reservation->customer->phone,
                        'email' => $reservation->customer->email,
                        'is_main' => true,
                    ];

                    $additionalGuests = $reservation->guests->map(function($guest) {
                        return [
                            'id' => $guest->id,
                            'name' => $guest->name,
                            'identification' => $guest->taxProfile?->identification ?? null,
                            'phone' => $guest->phone,
                            'email' => $guest->email,
                            'is_main' => false,
                        ];
                    })->toArray();

                    $guestsData = array_merge([$mainGuest], $additionalGuests);
                }

                // Snapshots are IMMUTABLE - only create if doesn't exist
                // Never update existing snapshots to preserve historical data integrity
                // This ensures that manually created snapshots (from cancellations) are not overwritten
                RoomDailyStatus::firstOrCreate(
                    [
                        'room_id' => $room->id,
                        'date' => $snapshotDate->toDateString(),
                    ],
                    [
                        'status' => $displayStatus, // Display status - what user sees (includes "Ocupada" from reservations)
                        'cleaning_status' => $cleaning['code'],
                        'reservation_id' => $reservation?->id,
                        'guest_name' => $reservation?->customer?->name ?? null,
                        'guests_data' => $guestsData,
                        'check_out_date' => $reservation?->check_out_date ?? null,
                        'total_amount' => $reservation?->total_amount ?? 0,
                    ]
                );
            }
        });

        $this->info("Snapshot generado para {$snapshotDate->toDateString()}.");
        return Command::SUCCESS;
    }
}

    }
}

    }
}

    }
}

    }
}

    }
}
