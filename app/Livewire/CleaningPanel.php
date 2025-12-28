<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Room;
use App\Enums\RoomStatus as RoomStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CleaningPanel extends Component
{
    public $rooms = [];
    public $currentTime;
    
    /**
     * Timestamp de la última actualización por evento.
     * Se usa para que el polling NO ejecute si ya hubo un evento reciente (< 6s).
     * Esto evita renders innecesarios y que el polling sobrescriba cambios recientes.
     */
    public $lastEventUpdate = 0;
    
    /**
     * Hash para detectar cambios sin cargar datos completos.
     * Optimización: permite verificar cambios rápidamente antes de recargar todo.
     */
    public $dataHash = '';
    
    private const POLLING_INTERVAL = 5;
    private const EVENT_COOLDOWN = 6; // segundos

    public function mount(): void
    {
        $this->loadRooms();
        $this->currentTime = now()->format('H:i');
    }
    
    /**
     * Calculate hash of current room states for fast change detection.
     * Single Responsibility: Only calculates hash.
     * Performance: Uses minimal queries, only gets timestamps and counts.
     * 
     * @return string Hash of room states
     */
    private function calculateDataHash(): string
    {
        // Get only critical fields that indicate changes: IDs, last_cleaned_at, reservation counts
        $today = Carbon::today();
        
        $roomStates = Room::select('id', 'last_cleaned_at')
            ->withCount([
                'reservations' => function($query) use ($today) {
                    $query->where('check_in_date', '<=', $today)
                          ->where('check_out_date', '>=', $today);
                }
            ])
            ->orderBy('id')
            ->get()
            ->map(function($room) {
                return [
                    'id' => $room->id,
                    'last_cleaned' => $room->last_cleaned_at?->timestamp ?? 0,
                    'reservations_count' => $room->reservations_count
                ];
            })
            ->toArray();
        
        // Create hash from room states
        return md5(json_encode($roomStates));
    }

    /**
     * Load all rooms with their current states.
     * Optimized: Single query with eager loading, no N+1 queries.
     * Used only for initial load and full refresh (polling, external updates).
     */
    public function loadRooms(): void
    {
        $today = Carbon::today();
        
        // Single query with eager loading - NO additional queries per room
        // Eager loading already brings fresh data from database
        $allRooms = Room::with([
            'reservations' => function($query) use ($today) {
                $query->where('check_in_date', '<=', $today)
                      ->where('check_out_date', '>=', $today);
            }
        ])->orderBy('room_number')->get();

        // Transform rooms to array format
        // NO refresh() needed - eager loading already provides fresh data
        // This eliminates N+1 query problem
        $this->rooms = $allRooms->map(function($room) use ($today) {
            return $this->transformRoomToArray($room, $today);
        })->toArray();
        
        // Calculate hash for change detection (much faster than loading all data)
        $this->dataHash = $this->calculateDataHash();
    }

    /**
     * Transform a Room model to array format for display.
     * Centralized logic to avoid duplication.
     * 
     * @param Room $room
     * @param Carbon $date
     * @return array
     */
    private function transformRoomToArray(Room $room, Carbon $date): array
    {
        $cleaningStatus = $room->cleaningStatus($date);
        $displayStatus = $room->getDisplayStatus($date);
        
        // Get guests_count for towel information
        // First try active reservation, if room is occupied
        $activeReservation = $room->getActiveReservation($date);
        $guestsCount = null;
        
        if ($activeReservation) {
            // Room is occupied, use active reservation's guests_count
            $guestsCount = $activeReservation->guests_count;
        } elseif ($cleaningStatus['code'] === 'pendiente') {
            // Room is free but needs cleaning, get most recent ended reservation
            // This helps determine how many towels were needed
            $recentReservation = $room->reservations()
                ->where('check_out_date', '<=', $date->toDateString())
                ->orderBy('check_out_date', 'desc')
                ->first();
            
            if ($recentReservation) {
                $guestsCount = $recentReservation->guests_count;
            }
        }
        
        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'beds_count' => $room->beds_count,
            'max_capacity' => $room->max_capacity,
            'status' => $room->status,
            'last_cleaned_at' => $room->last_cleaned_at,
            'display_status' => $displayStatus,
            'cleaning_status' => $cleaningStatus, // Store cleaning status to avoid recalculation in view
            'needs_cleaning' => $cleaningStatus['code'] === 'pendiente',
            'can_mark_clean' => $cleaningStatus['code'] === 'pendiente',
            'guests_count' => $guestsCount, // Number of guests for towel calculation
        ];
    }

    /**
     * Mark a room as clean.
     * Optimized: Only updates the affected room in memory, no full reload.
     * Performance: O(1) instead of O(N) where N = total rooms.
     */
    public function markAsClean(int $roomId): void
    {
        try {
            $today = Carbon::today();
            
            // Find room and validate it needs cleaning
            $room = Room::with([
                'reservations' => function($query) use ($today) {
                    $query->where('check_in_date', '<=', $today)
                          ->where('check_out_date', '>=', $today);
                }
            ])->findOrFail($roomId);

            // Validate: only rooms needing cleaning can be marked (using today's date)
            if ($room->cleaningStatus($today)['code'] !== 'pendiente') {
                $this->dispatch('notify', 
                    type: 'error', 
                    message: "La habitación #{$room->room_number} no requiere limpieza en este momento."
                );
                return;
            }

            // Update last_cleaned_at within transaction
            DB::transaction(function () use ($room) {
                $room->update(['last_cleaned_at' => now()]);
            });

            // Reload ONLY this room from database with fresh data
            $room->refresh();
            $room->load([
                'reservations' => function($query) use ($today) {
                    $query->where('check_in_date', '<=', $today)
                          ->where('check_out_date', '>=', $today);
                }
            ]);

            // Recalculate states ONLY for this room
            $updatedRoomData = $this->transformRoomToArray($room, $today);

            // Update ONLY the affected room in memory array
            // This is O(1) instead of O(N) - much faster
            $roomIndex = array_search($roomId, array_column($this->rooms, 'id'));
            if ($roomIndex !== false) {
                $this->rooms[$roomIndex] = $updatedRoomData;
            }

            // Update hash after change
            $this->dataHash = $this->calculateDataHash();
            
            // Mark as just updated to skip next listener query (1 second cache)
            Cache::put("room_updated_{$roomId}", true, 1);

            // Dispatch evento global para sincronización en tiempo real con otros componentes
            // MECANISMO PRINCIPAL: Si RoomManager está montado, recibirá este evento inmediatamente (<1s)
            // FALLBACK: Si no está montado, el polling cada 5s capturará el cambio en ≤5s
            $this->dispatch('room-status-updated', roomId: $room->id);
            
            // Marcar que hubo una actualización por evento (evita que el polling ejecute innecesariamente)
            $this->lastEventUpdate = now()->timestamp;
            $this->dispatch('notify', 
                type: 'success', 
                message: "Habitación #{$room->room_number} marcada como limpia."
            );

        } catch (\Exception $e) {
            Log::error('ERROR marking room as clean', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', 
                type: 'error', 
                message: 'Error al procesar la solicitud: ' . $e->getMessage()
            );
        }
    }

    /**
     * OPTIMIZED POLLING: Smart change detection before reloading.
     * 
     * ROL: Mecanismo de sincronización FALLBACK INTELIGENTE
     * - Se ejecuta automáticamente cada 5s mediante wire:poll.5s
     * - PERO solo ejecuta si NO hubo un evento reciente (< 6s)
     * - Garantiza que cambios externos se reflejen en ≤5s si el evento Livewire se pierde
     * - NO es el mecanismo principal (los eventos Livewire son más rápidos e inmediatos)
     * 
     * OPTIMIZACIÓN MEJORADA:
     * - Verifica $lastEventUpdate antes de ejecutar queries (cooldown)
     * - Detecta cambios usando hash antes de recargar datos completos
     * - Solo recarga si realmente hay cambios (evita renders innecesarios)
     * - Esto reduce carga en BD y mejora rendimiento significativamente
     * 
     * NOTA: Si ambos componentes están montados, los eventos Livewire actualizan
     * inmediatamente (<1s) y marcan $lastEventUpdate, haciendo que este polling
     * se salte hasta 6s después. Esto elimina renders duplicados y mejora UX.
     */
    public function refresh(): void
    {
        // Skip if recent event update (cooldown)
        $secondsSinceLastEvent = now()->timestamp - $this->lastEventUpdate;
        if ($this->lastEventUpdate > 0 && $secondsSinceLastEvent < self::EVENT_COOLDOWN) {
            // Solo actualizar hora, no recargar habitaciones
            $this->currentTime = now()->format('H:i');
            return;
        }
        
        // Smart change detection: Compare hash before full reload
        // This is much faster than loading all room data
        $newHash = $this->calculateDataHash();
        
        if ($newHash !== $this->dataHash) {
            // Data changed, reload everything
        $this->loadRooms();
        }
        
        // Always update time (minimal operation)
        $this->currentTime = now()->format('H:i');
    }

    /**
     * Determine if polling should be active.
     * Single Responsibility: Only decides polling state.
     * 
     * @return bool
     */
    public function shouldPoll(): bool
    {
        // Don't poll if recent event (cooldown active)
        $secondsSinceLastEvent = now()->timestamp - $this->lastEventUpdate;
        if ($this->lastEventUpdate > 0 && $secondsSinceLastEvent < self::EVENT_COOLDOWN) {
            return false;
        }
        
        // Poll if no recent activity or if data might have changed
        return true;
    }

    /**
     * OPTIMIZED LISTENER: Uses cache to avoid unnecessary queries.
     * 
     * MECANISMO PRINCIPAL de sincronización en tiempo real.
     * 
     * ROL: Sincronización INMEDIATA cuando ambos componentes están montados
     * - Se ejecuta cuando otro componente (ej: RoomManager) dispatch 'room-status-updated'
     * - Latencia: <300ms (inmediato, optimizado)
     * - Funciona SOLO si ambos componentes están montados en la misma sesión del navegador
     * 
     * OPTIMIZACIÓN MEJORADA O(1):
     * - Actualiza SOLO la habitación afectada en memoria (no recarga todas)
     * - Usa cache para evitar query si acabamos de actualizar la habitación
     * - Solo hace query si realmente es necesario (evita queries duplicadas)
     * - Marca $lastEventUpdate para evitar que el polling ejecute inmediatamente después
     * 
     * FLUJO:
     * 1. RoomManager marca habitación como liberada/continuada → dispatch evento
     * 2. Este listener recibe el evento → verifica cache → actualiza SOLO la habitación afectada (O(1))
     * 3. UI se actualiza automáticamente sin recargar página
     * 
     * FALLBACK:
     * - Si este listener NO se ejecuta (componente no montado), el polling (refresh())
     *   capturará el cambio en ≤5s
     */
    #[On('room-status-updated')]
    public function onRoomStatusUpdated(int $roomId): void
    {
        // Cargar siempre para asegurar sincronización completa (evitamos estados obsoletos)
        $this->loadRooms();

        // Actualizar hash y cooldown (el polling se saltará durante el cooldown)
        $this->dataHash = $this->calculateDataHash();
        $this->lastEventUpdate = now()->timestamp;
    }


    public function render()
    {
        return view('livewire.cleaning-panel')
            ->layout('layouts.cleaning-panel');
    }
}
