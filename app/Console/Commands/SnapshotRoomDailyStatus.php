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
                $displayStatus = $room->getDisplayStatus($snapshotDate);
                $cleaning = $room->cleaningStatus($snapshotDate);

                $reservation = $room->getActiveReservation($snapshotDate);
                if (!$reservation && $displayStatus === RoomStatus::PENDIENTE_CHECKOUT) {
                    $reservation = $room->getPendingCheckoutReservation($snapshotDate);
                }

                RoomDailyStatus::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'date' => $snapshotDate->toDateString(),
                    ],
                    [
                        'status' => $displayStatus,
                        'cleaning_status' => $cleaning['code'],
                        'reservation_id' => $reservation?->id,
                        'guest_name' => $reservation?->customer?->name,
                        'total_amount' => $reservation?->total_amount ?? 0,
                    ]
                );
            }
        });

        $this->info("Snapshot generado para {$snapshotDate->toDateString()}.");
        return Command::SUCCESS;
    }
}
