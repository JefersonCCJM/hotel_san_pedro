<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Log reservation creation.
     */
    public function logReservationCreated(
        Reservation $reservation,
        Request $request,
        array $roomIds
    ): void {
        try {
            // Ensure customer is loaded
            if (!$reservation->relationLoaded('customer')) {
                $reservation->load('customer');
            }

            $customer = $reservation->customer;
            $roomNumbers = Room::whereIn('id', $roomIds)->pluck('room_number')->toArray();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'reservation_created',
                'description' => "Creó la reserva #{$reservation->id} para el cliente {$customer->name}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'customer_name' => $customer->name,
                    'room_ids' => $roomIds,
                    'room_numbers' => $roomNumbers,
                    'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                    'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                    'total_amount' => (float) $reservation->total_amount,
                    'deposit' => (float) $reservation->deposit,
                    'guests_count' => (int) $reservation->guests_count,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for reservation_created', [
                'exception' => $e,
                'reservation_id' => $reservation->id ?? null,
            ]);
            // Don't throw - audit logging should never break the main flow
        }
    }

    /**
     * Log reservation update with change tracking.
     */
    public function logReservationUpdated(
        Reservation $reservation,
        Request $request,
        array $oldValues
    ): void {
        try {
            // Ensure relationships are loaded
            if (!$reservation->relationLoaded('customer')) {
                $reservation->load('customer');
            }
            if (!$reservation->relationLoaded('room')) {
                $reservation->load('room');
            }

            $customer = $reservation->customer;
            $room = $reservation->room;

            $newValues = [
                'room_id' => $reservation->room_id,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'total_amount' => (float) $reservation->total_amount,
                'deposit' => (float) $reservation->deposit,
                'guests_count' => (int) $reservation->guests_count,
                'payment_method' => $reservation->payment_method,
            ];

            $changes = [];
            foreach ($newValues as $key => $newValue) {
                if (isset($oldValues[$key]) && $oldValues[$key] !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValues[$key],
                        'new' => $newValue,
                    ];
                }
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'reservation_updated',
                'description' => "Actualizó la reserva #{$reservation->id} del cliente {$customer->name}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'customer_name' => $customer->name,
                    'room_id' => $reservation->room_id,
                    'room_number' => $room ? $room->room_number : null,
                    'changes' => $changes,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for reservation_updated', [
                'exception' => $e,
                'reservation_id' => $reservation->id ?? null,
            ]);
            // Don't throw - audit logging should never break the main flow
        }
    }

    /**
     * Log reservation cancellation.
     */
    public function logReservationCancelled(
        Reservation $reservation,
        Request $request
    ): void {
        try {
            // Ensure customer is loaded (even if reservation is soft deleted)
            if (!$reservation->relationLoaded('customer')) {
                $reservation->load('customer');
            }

            $customer = $reservation->customer;

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'reservation_cancelled',
                'description' => "Canceló la reserva #{$reservation->id} del cliente {$customer->name}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'customer_name' => $customer->name,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for reservation_cancelled', [
                'exception' => $e,
                'reservation_id' => $reservation->id ?? null,
            ]);
            // Don't throw - audit logging should never break the main flow
        }
    }
}

