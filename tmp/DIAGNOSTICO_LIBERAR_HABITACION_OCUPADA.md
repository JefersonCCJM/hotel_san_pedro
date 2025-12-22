# Diagnóstico: Habitación Liberada Sigue Mostrando OCUPADA

**Fecha:** 2025-01-27  
**Problema:** Usuario libera habitación (ej. 201) pero el módulo la sigue mostrando OCUPADA sin recargar.  
**Objetivo:** Determinar si es comportamiento correcto o bug, y proponer solución.

---

## 1. CASO REPRODUCIBLE (PASO A PASO)

### Escenario Exacto

**Datos iniciales en BD:**
```sql
-- Habitación 201
rooms.id = 201 (o el ID correspondiente)
rooms.room_number = '201'
rooms.status = 'libre' (o cualquier valor, no importa para display_status)
rooms.last_cleaned_at = '2025-01-25 10:00:00' (o NULL)

-- Reserva activa
reservations.id = 123
reservations.room_id = 201
reservations.check_in_date = '2025-01-27'
reservations.check_out_date = '2025-01-30'
reservations.status = 'confirmed'
```

**Estado en el módulo:**
- Usuario selecciona fecha: `2025-01-28` (valor de `$this->date = '2025-01-28'`)
- La habitación 201 se muestra como **OCUPADA** porque:
  - `check_in_date (2025-01-27) <= $date (2025-01-28)` ✅
  - `check_out_date (2025-01-30) > $date (2025-01-28)` ✅
  - Por lo tanto: `isOccupied('2025-01-28')` retorna `true`
  - Por lo tanto: `getDisplayStatus('2025-01-28')` retorna `OCUPADA` (prioridad 2)

**Acción del usuario:**
- Usuario hace clic en "Liberar" → se ejecuta `releaseRoom(201, 'libre')`
- `$date = Carbon::parse('2025-01-28')->startOfDay()` (fecha seleccionada, NO hoy)

**¿Qué pasa en `releaseRoom()`?**

1. **Busca reserva activa:**
   ```php
   $reservation = $room->reservations()
       ->where('check_in_date', '<=', '2025-01-28')
       ->where('check_out_date', '>', '2025-01-28')  // ← AQUÍ ESTÁ EL PROBLEMA
       ->first();
   ```
   - Encuentra la reserva 123 porque `check_out_date (2025-01-30) > 2025-01-28` ✅

2. **Evalúa qué hacer con la reserva:**
   - `$start = '2025-01-27'`, `$end = '2025-01-30'`
   - `$date = '2025-01-28'`
   - **CASO 4 (línea 332):** El día está en la mitad → Divide la reserva
   ```php
   $reservation->update(['check_out_date' => '2025-01-28']);  // ← Cambia check_out_date
   $newRes = $reservation->replicate();
   $newRes->check_in_date = '2025-01-29';
   $newRes->check_out_date = '2025-01-30';
   $newRes->save();
   ```

3. **Actualiza `rooms.status`:**
   ```php
   if ($date->isToday() && !$room->isOccupied($date)) {
       // ...
   }
   ```
   - **PROBLEMA:** `$date->isToday()` es `false` si el usuario seleccionó `2025-01-28` y hoy es `2025-01-27`
   - **NO entra en este bloque** si la fecha seleccionada NO es hoy
   - Por lo tanto: `rooms.status` **NO se actualiza**

4. **Refresca datos:**
   - `$room->refresh()`
   - `$room->unsetRelation('reservations')`
   - `$this->refreshRooms()` → llama a `render()`

5. **En `render()` (transform):**
   ```php
   $room->display_status = $room->getDisplayStatus('2025-01-28');
   ```
   - `isOccupied('2025-01-28')` ahora consulta:
     ```sql
     WHERE check_in_date <= '2025-01-28' AND check_out_date > '2025-01-28'
     ```
   - La reserva original ahora tiene `check_out_date = '2025-01-28'`
   - **PERO:** `check_out_date = '2025-01-28'` NO es `> '2025-01-28'` ❌
   - **DEBERÍA retornar `false`** y mostrar `LIBRE` o `PENDIENTE_ASEO`

### ¿Por Qué Sigue Mostrando OCUPADA?

**HIPÓTESIS 1: Cache de relaciones**
- `$room->unsetRelation('reservations')` limpia el cache del modelo individual
- **PERO:** En `render()`, se hace `Room::with('reservations')` que carga TODAS las reservas del mes
- Si la query de `with()` se ejecutó ANTES de que `releaseRoom()` actualizara la BD, puede traer datos viejos

**HIPÓTESIS 2: Fecha mal normalizada**
- `releaseRoom()` usa `$date->toDateString()` (línea 329, 336)
- `render()` usa `Carbon::parse($res->check_out_date)->startOfDay()` (línea 594)
- Si hay diferencia de formato (ej: `'2025-01-28'` vs `'2025-01-28 00:00:00'`), la comparación puede fallar

**HIPÓTESIS 3: Reserva nueva creada**
- Si se creó `$newRes` con `check_in_date = '2025-01-29'`, esa reserva NO debería afectar `isOccupied('2025-01-28')`
- **PERO:** Si hay otra reserva activa que no se modificó, seguiría mostrando OCUPADA

**EVIDENCIA NECESARIA:** Logs en tiempo real para confirmar qué está pasando.

---

## 2. PRUEBA EN CÓDIGO: ¿QUÉ CONDICIÓN EXACTA ESTÁ DANDO OCUPADA?

### Logs Temporales a Agregar

#### A) En `Room::isOccupied()` (app/Models/Room.php:72-82)

**Agregar después de la línea 74:**
```php
public function isOccupied(?\Carbon\Carbon $date = null): bool
{
    $date = $date ?? \Carbon\Carbon::today();
    
    // LOG TEMPORAL - ELIMINAR DESPUÉS
    \Log::channel('single')->info('[DIAG] Room::isOccupied', [
        'room_id' => $this->id,
        'room_number' => $this->room_number,
        'date' => $date->toDateString(),
        'date_formatted' => $date->format('Y-m-d H:i:s'),
    ]);

    // Room is occupied if: check_in <= date AND check_out > date
    return $this->reservations()
        ->where('check_in_date', '<=', $date)
        ->where('check_out_date', '>', $date)
        ->exists();
}
```

**Agregar DESPUÉS de la query (antes del return):**
```php
// LOG TEMPORAL - ELIMINAR DESPUÉS
$reservations = $this->reservations()
    ->where('check_in_date', '<=', $date)
    ->where('check_out_date', '>', $date)
    ->get(['id', 'check_in_date', 'check_out_date']);

\Log::channel('single')->info('[DIAG] Room::isOccupied - Reservations found', [
    'room_id' => $this->id,
    'date' => $date->toDateString(),
    'reservations_count' => $reservations->count(),
    'reservations' => $reservations->map(function($r) {
        return [
            'id' => $r->id,
            'check_in_date' => $r->check_in_date,
            'check_out_date' => $r->check_out_date,
            'check_in_parsed' => \Carbon\Carbon::parse($r->check_in_date)->toDateString(),
            'check_out_parsed' => \Carbon\Carbon::parse($r->check_out_date)->toDateString(),
        ];
    })->toArray(),
    'result' => $reservations->isNotEmpty(),
]);

return $reservations->isNotEmpty();
```

#### B) En `RoomManager::render()` - Transform (app/Livewire/RoomManager.php:583-639)

**Agregar al inicio del closure (después de línea 583):**
```php
$rooms->getCollection()->transform(function($room) use ($date) {
    // LOG TEMPORAL - ELIMINAR DESPUÉS
    \Log::channel('single')->info('[DIAG] RoomManager::render - Transform start', [
        'room_id' => $room->id,
        'room_number' => $room->room_number,
        'date' => $date->toDateString(),
        'rooms_status_db' => $room->status->value,
    ]);
    
    $isFuture = $date->isAfter(now()->endOfDay());
    $reservation = null;
    // ... resto del código ...
```

**Agregar DESPUÉS de buscar reserva activa (después de línea 597):**
```php
// First, try to find active reservation (check_out_date > $date)
$reservation = $room->reservations->first(function($res) use ($date) {
    $checkIn = Carbon::parse($res->check_in_date)->startOfDay();
    $checkOut = Carbon::parse($res->check_out_date)->startOfDay();
    // Habitación ocupada si: check_in_date <= $date AND check_out_date > $date
    return $checkIn->lte($date) && $checkOut->gt($date);
});

// LOG TEMPORAL - ELIMINAR DESPUÉS
\Log::channel('single')->info('[DIAG] RoomManager::render - Active reservation search', [
    'room_id' => $room->id,
    'date' => $date->toDateString(),
    'reservations_loaded' => $room->reservations->map(function($r) {
        return [
            'id' => $r->id,
            'check_in_date' => $r->check_in_date,
            'check_out_date' => $r->check_out_date,
            'check_in_parsed' => \Carbon\Carbon::parse($r->check_in_date)->startOfDay()->toDateString(),
            'check_out_parsed' => \Carbon\Carbon::parse($r->check_out_date)->startOfDay()->toDateString(),
        ];
    })->toArray(),
    'current_reservation_id' => $reservation?->id,
    'current_reservation_check_out' => $reservation?->check_out_date,
]);
```

**Agregar DESPUÉS de asignar display_status (después de línea 636):**
```php
// Use getDisplayStatus() with the selected date to get correct status
$room->display_status = $room->getDisplayStatus($date);

// LOG TEMPORAL - ELIMINAR DESPUÉS
\Log::channel('single')->info('[DIAG] RoomManager::render - Display status calculated', [
    'room_id' => $room->id,
    'date' => $date->toDateString(),
    'display_status' => $room->display_status->value,
    'rooms_status_db' => $room->status->value,
    'is_occupied' => $room->isOccupied($date),
    'cleaning_status_code' => $room->cleaningStatus()['code'],
]);
```

#### C) En `RoomManager::releaseRoom()` (app/Livewire/RoomManager.php:290-380)

**Agregar al inicio del método (después de línea 294):**
```php
public function releaseRoom($roomId, $targetStatus)
{
    $room = Room::findOrFail($roomId);
    $room->refresh();
    $date = Carbon::parse($this->date)->startOfDay();
    
    // LOG TEMPORAL - ELIMINAR DESPUÉS
    \Log::channel('single')->info('[DIAG] RoomManager::releaseRoom - START', [
        'room_id' => $roomId,
        'room_number' => $room->room_number,
        'target_status' => $targetStatus,
        'date_selected' => $date->toDateString(),
        'date_is_today' => $date->isToday(),
        'rooms_status_before' => $room->status->value,
    ]);
```

**Agregar DESPUÉS de modificar reserva (después de línea 342):**
```php
if ($reservation) {
    // ... lógica de modificación ...
}

// LOG TEMPORAL - ELIMINAR DESPUÉS
\Log::channel('single')->info('[DIAG] RoomManager::releaseRoom - After reservation update', [
    'room_id' => $roomId,
    'reservation_modified' => $reservation ? true : false,
    'reservation_id' => $reservation?->id,
    'reservation_check_out_after' => $reservation?->check_out_date,
    'is_occupied_after' => $room->isOccupied($date),
]);
```

**Agregar DESPUÉS de actualizar rooms.status (después de línea 361):**
```php
// ... lógica de actualización ...

// LOG TEMPORAL - ELIMINAR DESPUÉS
\Log::channel('single')->info('[DIAG] RoomManager::releaseRoom - After status update', [
    'room_id' => $roomId,
    'rooms_status_after' => $room->fresh()->status->value,
    'status_was_updated' => $room->wasChanged('status'),
]);
```

### Ejemplo de Salida Esperada del Log

**Antes de liberar:**
```
[2025-01-28 10:00:00] local.INFO: [DIAG] RoomManager::render - Transform start {"room_id":201,"room_number":"201","date":"2025-01-28","rooms_status_db":"libre"}
[2025-01-28 10:00:00] local.INFO: [DIAG] RoomManager::render - Active reservation search {"room_id":201,"date":"2025-01-28","reservations_loaded":[{"id":123,"check_in_date":"2025-01-27","check_out_date":"2025-01-30","check_in_parsed":"2025-01-27","check_out_parsed":"2025-01-30"}],"current_reservation_id":123,"current_reservation_check_out":"2025-01-30"}
[2025-01-28 10:00:00] local.INFO: [DIAG] Room::isOccupied {"room_id":201,"room_number":"201","date":"2025-01-28","date_formatted":"2025-01-28 00:00:00"}
[2025-01-28 10:00:00] local.INFO: [DIAG] Room::isOccupied - Reservations found {"room_id":201,"date":"2025-01-28","reservations_count":1,"reservations":[{"id":123,"check_in_date":"2025-01-27","check_out_date":"2025-01-30","check_in_parsed":"2025-01-27","check_out_parsed":"2025-01-30"}],"result":true}
[2025-01-28 10:00:00] local.INFO: [DIAG] RoomManager::render - Display status calculated {"room_id":201,"date":"2025-01-28","display_status":"ocupada","rooms_status_db":"libre","is_occupied":true,"cleaning_status_code":"limpia"}
```

**Después de liberar:**
```
[2025-01-28 10:01:00] local.INFO: [DIAG] RoomManager::releaseRoom - START {"room_id":201,"room_number":"201","target_status":"libre","date_selected":"2025-01-28","date_is_today":true,"rooms_status_before":"libre"}
[2025-01-28 10:01:00] local.INFO: [DIAG] RoomManager::releaseRoom - After reservation update {"room_id":201,"reservation_modified":true,"reservation_id":123,"reservation_check_out_after":"2025-01-28","is_occupied_after":false}
[2025-01-28 10:01:00] local.INFO: [DIAG] RoomManager::releaseRoom - After status update {"room_id":201,"rooms_status_after":"libre","status_was_updated":false}
[2025-01-28 10:01:01] local.INFO: [DIAG] RoomManager::render - Transform start {"room_id":201,"room_number":"201","date":"2025-01-28","rooms_status_db":"libre"}
[2025-01-28 10:01:01] local.INFO: [DIAG] RoomManager::render - Active reservation search {"room_id":201,"date":"2025-01-28","reservations_loaded":[{"id":123,"check_in_date":"2025-01-27","check_out_date":"2025-01-28","check_in_parsed":"2025-01-27","check_out_parsed":"2025-01-28"}],"current_reservation_id":null,"current_reservation_check_out":null}
[2025-01-28 10:01:01] local.INFO: [DIAG] Room::isOccupied {"room_id":201,"date":"2025-01-28","date_formatted":"2025-01-28 00:00:00"}
[2025-01-28 10:01:01] local.INFO: [DIAG] Room::isOccupied - Reservations found {"room_id":201,"date":"2025-01-28","reservations_count":0,"reservations":[],"result":false}
[2025-01-28 10:01:01] local.INFO: [DIAG] RoomManager::render - Display status calculated {"room_id":201,"date":"2025-01-28","display_status":"pendiente_aseo","rooms_status_db":"libre","is_occupied":false,"cleaning_status_code":"pendiente"}
```

**Interpretación:**
- Si `is_occupied_after: false` pero `display_status: "ocupada"` → **BUG confirmado**
- Si `reservations_loaded` muestra `check_out_date: "2025-01-30"` después de liberar → **Cache de relaciones**

---

## 3. REVISIÓN DE releaseRoom() / LIBERAR HABITACIÓN

### Método Exacto

**Ubicación:** `app/Livewire/RoomManager.php:290-380`  
**Nombre:** `releaseRoom($roomId, $targetStatus)`  
**Llamado desde:** `resources/views/livewire/room-manager.blade.php:616`  
```javascript
@this.releaseRoom(roomId, result.isConfirmed ? 'libre' : 'sucia');
```

### Cambios en Base de Datos

#### A) Tabla `reservations`

**SÍ se modifica `check_out_date`** (líneas 329, 336):
- **CASO 3 (línea 326):** Si es el último día ocupado → `check_out_date = $date`
- **CASO 4 (línea 332):** Si el día está en la mitad → `check_out_date = $date` Y se crea nueva reserva

**NO se modifica:**
- `reservation.status` (no existe este campo o no se toca)
- `reservation.check_in_date` (excepto CASO 2, línea 323)

**Ejemplo:**
```sql
-- ANTES
reservations.id = 123
reservations.check_in_date = '2025-01-27'
reservations.check_out_date = '2025-01-30'

-- DESPUÉS (CASO 4)
reservations.id = 123
reservations.check_in_date = '2025-01-27'
reservations.check_out_date = '2025-01-28'  ← CAMBIADO

reservations.id = 124 (nueva)
reservations.check_in_date = '2025-01-29'
reservations.check_out_date = '2025-01-30'
```

#### B) Tabla `rooms`

**CONDICIONALMENTE se modifica `rooms.status`** (líneas 353-361):
```php
if ($date->isToday() && !$room->isOccupied($date)) {
    if ($statusEnum === RoomStatus::SUCIA) {
        $room->update(['status' => $statusEnum]);
    }
}
```

**Condiciones:**
1. `$date->isToday()` → **DEBE ser verdadero** (fecha seleccionada = hoy)
2. `!$room->isOccupied($date)` → **DEBE ser verdadero** (no hay reserva activa después de modificar)
3. `$statusEnum === RoomStatus::SUCIA` → Solo si se libera como "Sucia"

**NO se modifica:**
- `rooms.last_cleaned_at` (no se toca en `releaseRoom()`)
- `rooms.status` si se libera como "Libre" (línea 354: "don't update status")

#### C) ¿Por Qué el Módulo Seguiría Mostrando OCUPADA?

**RAZÓN 1: Fecha seleccionada NO es hoy**
- Si usuario selecciona `2025-01-28` pero hoy es `2025-01-27`:
  - `$date->isToday()` = `false`
  - **NO entra en el bloque** que actualiza `rooms.status`
  - `rooms.status` queda igual
  - **PERO:** `check_out_date` SÍ se modifica
  - `isOccupied('2025-01-28')` debería retornar `false` después de modificar
  - **DEBERÍA mostrar `LIBRE` o `PENDIENTE_ASEO`** si no hay reserva activa

**RAZÓN 2: Cache de relaciones en `render()`**
- `render()` hace `Room::with('reservations')` que carga reservas del mes
- Si la query se ejecutó ANTES de que `releaseRoom()` actualizara la BD:
  - Las reservas en memoria tienen `check_out_date = '2025-01-30'` (viejo)
  - `isOccupied()` usa esas reservas cacheadas
  - Retorna `true` aunque en BD ya esté actualizado

**RAZÓN 3: `unsetRelation()` no afecta la query de `render()`**
- `$room->unsetRelation('reservations')` (línea 348) limpia el cache del modelo `$room`
- **PERO:** `render()` crea una NUEVA query con `Room::with('reservations')`
- Si hay un problema de timing o transacción, puede traer datos viejos

**RAZÓN 4: Reserva nueva creada afecta fecha futura**
- Si se creó `$newRes` con `check_in_date = '2025-01-29'`:
  - Para fecha `2025-01-28`: NO debería afectar (check_in > date)
  - **PERO:** Si hay otra reserva activa que no se modificó, seguiría mostrando OCUPADA

---

## 4. DECISIÓN DE NEGOCIO (PREGUNTA CERRADA)

### Pregunta 1: ¿"Liberar habitación" debería hacer checkout/cerrar la reserva activa?

**Respuesta: SÍ** ✅

**Justificación:**
- El término "liberar habitación" en hotelería significa **hacer checkout del huésped**.
- Si el usuario hace clic en "Liberar", espera que la habitación quede **disponible inmediatamente**.
- El comportamiento actual SÍ modifica `check_out_date` para cerrar la reserva, lo cual es correcto.
- **PERO:** El problema es que el frontend no refleja el cambio sin recargar, lo cual es un bug de sincronización.

### Pregunta 2: ¿"Liberar habitación" solo cambia estado físico pero si hay reserva activa igual debe verse OCUPADA?

**Respuesta: NO** ❌

**Justificación:**
- Si se "libera" una habitación, la reserva se cierra (`check_out_date` se ajusta).
- Después de liberar, **NO debería haber reserva activa** para la fecha seleccionada.
- Por lo tanto, `display_status` debería cambiar a `LIBRE` o `PENDIENTE_ASEO`, **NO** `OCUPADA`.

### Recomendación Única

**Comportamiento esperado:**
1. Usuario selecciona fecha `2025-01-28` y ve habitación OCUPADA (hay reserva hasta `2025-01-30`).
2. Usuario hace clic en "Liberar" → se ejecuta `releaseRoom(201, 'libre')`.
3. `releaseRoom()` modifica `check_out_date = '2025-01-28'` (cierra la reserva).
4. `releaseRoom()` llama a `refreshRooms()` → `render()` se ejecuta.
5. `render()` calcula `display_status` con fecha `2025-01-28`:
   - `isOccupied('2025-01-28')` → `false` (check_out_date = '2025-01-28', no es > '2025-01-28')
   - `getDisplayStatus('2025-01-28')` → `PENDIENTE_ASEO` (si necesita limpieza) o `LIBRE`
6. **UI muestra `PENDIENTE_ASEO` o `LIBRE` inmediatamente, sin recargar.**

**Si esto NO ocurre → BUG confirmado.**

---

## 5. PROPUESTA DE SOLUCIÓN (AÚN SIN IMPLEMENTAR)

### Opción 1: Mínimo Cambio - Asegurar Sincronización y Recálculo Correcto

#### Qué Tocar

**A) `app/Livewire/RoomManager.php`:**

1. **En `releaseRoom()` (línea 370):**
   - Agregar `$room->refresh()` DESPUÉS de `unsetRelation()` para forzar recarga desde BD.
   - Agregar `DB::commit()` explícito si hay transacción (aunque no parece haberla).

2. **En `refreshRooms()` (línea 509):**
   - Agregar `$this->skipRender = false` para forzar re-render.
   - O mejor: llamar a `$this->render()` explícitamente y cachear resultado.

3. **En `render()` (línea 573):**
   - Cambiar `with('reservations')` a `with(['reservations' => function($q) use ($date) { ... }])` para cargar solo reservas relevantes a la fecha.
   - Agregar `->fresh()` o `->refresh()` a cada `$room` en el transform para asegurar datos frescos.

**B) `app/Models/Room.php`:**

1. **En `isOccupied()` (línea 78):**
   - Agregar `->fresh()` o `->refresh()` antes de consultar reservas para evitar cache.
   - O mejor: usar `$this->reservations()->getQuery()->fresh()`.

#### Riesgos

- **Bajo riesgo:** Cambios son principalmente de sincronización, no de lógica de negocio.
- **Riesgo medio:** `fresh()` en cada `$room` puede impactar performance si hay muchas habitaciones.
- **Mitigación:** Usar `fresh()` solo cuando sea necesario (ej: después de `releaseRoom()`).

#### Cómo Probar

1. **Test manual:**
   - Crear reserva activa para habitación 201 (check_in = hoy, check_out = +3 días).
   - Seleccionar fecha = hoy en el módulo.
   - Verificar que muestra OCUPADA.
   - Hacer clic en "Liberar".
   - **Verificar que cambia a PENDIENTE_ASEO o LIBRE inmediatamente, sin recargar.**

2. **Test con logs:**
   - Agregar logs temporales (sección 2).
   - Ejecutar el flujo y verificar en logs:
     - `is_occupied_after: false` después de liberar.
     - `display_status: "pendiente_aseo"` o `"libre"` en el siguiente render.

3. **Test con Tinker:**
   ```php
   $room = Room::find(201);
   $date = Carbon::parse('2025-01-28');
   $room->reservations()->where('check_in_date', '<=', $date)->where('check_out_date', '>', $date)->get();
   // Debería retornar vacío después de liberar
   ```

---

### Opción 2: Más Robusto - Formalizar Máquina de Estados / Checkout Explícito

#### Qué Tocar

**A) `app/Models/Room.php`:**

1. **Agregar método `checkout($date): void`:**
   ```php
   public function checkout(\Carbon\Carbon $date): void
   {
       DB::transaction(function() use ($date) {
           $reservation = $this->getActiveReservation($date);
           if ($reservation) {
               $reservation->update(['check_out_date' => $date->toDateString()]);
           }
           $this->refresh();
       });
   }
   ```

2. **Modificar `getDisplayStatus()` para ser más explícito:**
   - Separar lógica de ocupación de lógica de estado físico.
   - Agregar validación explícita: si `check_out_date == $date`, considerar como "liberada hoy".

**B) `app/Livewire/RoomManager.php`:**

1. **Refactorizar `releaseRoom()`:**
   - Llamar a `$room->checkout($date)` en lugar de lógica inline.
   - Separar "liberar" (checkout) de "marcar como sucia" (cambio de estado físico).

2. **Agregar método `forceRefreshRoom(int $roomId): void`:**
   ```php
   public function forceRefreshRoom(int $roomId): void
   {
       $room = Room::findOrFail($roomId);
       $room->refresh();
       $room->unsetRelation('reservations');
       $this->refreshRooms();
   }
   ```

3. **En `render()`:**
   - Agregar validación explícita: si `check_out_date == $date`, NO considerar como ocupada.

**C) Crear `app/Services/RoomStatusService.php` (nuevo):**

```php
class RoomStatusService
{
    public function calculateDisplayStatus(Room $room, \Carbon\Carbon $date): RoomStatus
    {
        // Lógica centralizada de cálculo de display_status
        // Separada del modelo para facilitar testing
    }
}
```

#### Riesgos

- **Alto riesgo:** Cambios arquitectónicos pueden romper otras partes del sistema.
- **Riesgo medio:** Requiere testing exhaustivo de todos los flujos.
- **Mitigación:** Implementar en fases, empezar con `checkout()` y luego refactorizar.

#### Cómo Probar

1. **Test unitario para `checkout()`:**
   ```php
   $room = Room::find(201);
   $date = Carbon::parse('2025-01-28');
   $room->checkout($date);
   $this->assertFalse($room->isOccupied($date));
   ```

2. **Test de integración:**
   - Mismo flujo que Opción 1, pero verificando que `checkout()` se ejecuta correctamente.

3. **Test de regresión:**
   - Verificar que `continueStay()`, `storeQuickRent()`, y otros métodos siguen funcionando.

---

### Recomendación Final

**Empezar con Opción 1** (mínimo cambio) porque:
- Es más segura (menos riesgo de romper cosas).
- Resuelve el problema inmediato (sincronización).
- Permite validar el comportamiento esperado antes de refactorizar.

**Si Opción 1 no resuelve completamente**, entonces considerar Opción 2 (más robusto) para formalizar la arquitectura.

---

## CONCLUSIÓN

**Comportamiento esperado:** Después de liberar, `display_status` debería cambiar a `LIBRE` o `PENDIENTE_ASEO` inmediatamente.

**Si NO ocurre → BUG confirmado**, probablemente por:
1. Cache de relaciones en `render()`.
2. Fecha mal normalizada en comparaciones.
3. Timing entre actualización de BD y query de `render()`.

**Siguiente paso:** Agregar logs temporales (sección 2) para confirmar la causa exacta, luego implementar Opción 1.

---

**Fin del diagnóstico.**

