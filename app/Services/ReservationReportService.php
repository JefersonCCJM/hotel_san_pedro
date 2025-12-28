<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReservationReportService
{
    /**
     * @return array{
     *   reservations: Collection<int, Reservation>,
     *   month: Carbon,
     *   start_date: Carbon,
     *   end_date: Carbon
     * }
     */
    public function getMonthlyReservations(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $monthDate->copy()->startOfMonth();
        $endOfMonth = $monthDate->copy()->endOfMonth();

        $reservations = Reservation::query()
            ->with(['customer', 'room', 'rooms'])
            ->where(function ($query) use ($startOfMonth, $endOfMonth): void {
                $query
                    ->whereBetween('check_in_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('check_out_date', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth): void {
                        $q->where('check_in_date', '<=', $startOfMonth)
                            ->where('check_out_date', '>=', $endOfMonth);
                    });
            })
            ->orderBy('check_in_date')
            ->orderBy('id')
            ->get();

        return [
            'reservations' => $reservations,
            'month' => $monthDate,
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
        ];
    }
}
