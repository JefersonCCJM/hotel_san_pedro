<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Enums\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PublicRoomStatusController extends Controller
{
    /**
     * Display the public room status view for cleaning staff.
     * No authentication required.
     */
    public function index(): View
    {
        $today = Carbon::today();
        
        $rooms = Room::with([
            'reservations' => function($query) use ($today) {
                $query->where('check_in_date', '<=', $today)
                      ->where('check_out_date', '>=', $today);
            }
        ])->orderBy('room_number')->get();

        $rooms->transform(function($room) use ($today) {
            // Check for reservations ending today (checkout pending)
            $reservationEndingToday = $room->reservations->first(function($res) use ($today) {
                $checkOutDate = Carbon::parse($res->check_out_date);
                return $checkOutDate->isSameDay($today);
            });
            
            // Check for active reservations (currently occupied)
            $activeReservation = $room->reservations->first(function($res) use ($today) {
                $checkIn = Carbon::parse($res->check_in_date);
                $checkOut = Carbon::parse($res->check_out_date);
                // Active if today is between check-in and check-out (excluding checkout day)
                return $today->between($checkIn, $checkOut->copy()->subDay());
            });
            
            // Determine display status based on physical status and reservations
            if ($reservationEndingToday && $room->status !== RoomStatus::LIBRE && $room->status !== RoomStatus::SUCIA && $room->status !== RoomStatus::LIMPIEZA) {
                // Checkout is today but room hasn't been marked as dirty/clean yet
                $room->display_status = RoomStatus::PENDIENTE_CHECKOUT;
            } elseif ($activeReservation) {
                // Room is currently occupied
                $room->display_status = RoomStatus::OCUPADA;
            } else {
                // No active reservation - use physical status
                $room->display_status = $room->status;
            }
            
            // Determine if button should be shown (only for SUCIA or LIMPIEZA status)
            // AND only if there's no active reservation blocking it
            $room->can_mark_clean = (
                ($room->status === RoomStatus::SUCIA || $room->status === RoomStatus::LIMPIEZA) &&
                !$activeReservation &&
                !$reservationEndingToday
            );
            
            return $room;
        });

        return view('public.room-status', compact('rooms'));
    }

    /**
     * Mark a room as clean (change from SUCIA to LIBRE).
     * Protected by rate limiting and strict validations.
     * Returns JSON always (API endpoint).
     */
    public function markClean(Request $request, Room $room): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Reload room to get latest status
            $room->refresh();

            // Validation 1: Room must exist
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Habitación no encontrada.'
                ], 404);
            }

            // Validation 2: Current status MUST be SUCIA or LIMPIEZA
            // Business rule: Only SUCIA -> LIBRE transition is allowed
            $previousStatus = $room->status;
            if ($room->status !== RoomStatus::SUCIA && $room->status !== RoomStatus::LIMPIEZA) {
                return response()->json([
                    'success' => false,
                    'message' => "Estado no permitido. La habitación #{$room->room_number} tiene estado: {$room->status->label()}. Solo se puede limpiar habitaciones marcadas como SUCIA.",
                    'current_status' => $room->status->value,
                    'current_status_label' => $room->status->label()
                ], 403);
            }

            // Validation 3: Check for active reservations that would block cleaning
            // Business rule: Cannot clean if room is OCCUPIED
            $today = Carbon::today();
            $activeReservation = $room->reservations()
                ->where('check_in_date', '<=', $today)
                ->where('check_out_date', '>=', $today)
                ->first();

            if ($activeReservation) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede limpiar. La habitación #{$room->room_number} está OCUPADA con una reserva activa.",
                    'reason' => 'occupied'
                ], 403);
            }

            // Validation 4: Check for reservations starting today
            // Business rule: Cannot clean if reservation starts today
            $reservationStartingToday = $room->reservations()
                ->where('check_in_date', $today)
                ->first();

            if ($reservationStartingToday) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede limpiar. La habitación #{$room->room_number} tiene una reserva que inicia hoy.",
                    'reason' => 'reservation_starts_today'
                ], 403);
            }

            // Validation 5: Check for pending checkout
            // Business rule: Cannot clean if checkout is pending
            $reservationEndingToday = $room->reservations()
                ->where('check_out_date', $today)
                ->first();

            if ($reservationEndingToday && $room->status !== RoomStatus::SUCIA && $room->status !== RoomStatus::LIMPIEZA) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede limpiar. La habitación #{$room->room_number} está PENDIENTE CHECKOUT.",
                    'reason' => 'pending_checkout'
                ], 403);
            }

            // All validations passed - update status to LIBRE
            $room->update([
                'status' => RoomStatus::LIBRE,
                'updated_at' => now()
            ]);

            // Reload to get updated status
            $room->refresh();

            DB::commit();

            Log::info('Room marked as clean via public module', [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'previous_status' => $previousStatus->value,
                'new_status' => RoomStatus::LIBRE->value,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Habitación #{$room->room_number} marcada como limpia y disponible.",
                'room' => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status->value,
                    'status_label' => $room->status->label()
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error marking room as clean', [
                'room_id' => $room->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud. Por favor, intente nuevamente.'
            ], 500);
        }
    }
}

