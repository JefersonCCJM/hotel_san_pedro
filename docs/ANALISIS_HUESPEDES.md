# Análisis del Sistema de Huéspedes

## 📊 Estructura de Base de Datos

### 1. Tablas Principales

#### `reservations`
- **ID del Cliente Principal**: `client_id` → `customers.id`
- El cliente principal de la reserva se almacena directamente aquí
- **NOTA**: Este es el único huésped que NO se guarda en las tablas de huéspedes

#### `reservation_rooms`
- **Relación**: Muchos-a-muchos entre `reservations` y `rooms`
- **Campos clave**: `reservation_id`, `room_id`
- Representa la asignación de una habitación a una reserva

#### `reservation_guests` ⚠️ **TABLA INTERMEDIA**
- **Propósito**: Guardar información de huéspedes adicionales por habitación
- **Estructura actual**:
  ```
  id (PK)
  reservation_room_id (FK → reservation_rooms.id)
  guest_id (FK → customers.id)
  is_primary (boolean)
  created_at, updated_at
  ```
- **IMPORTANTE**: Esta tabla ya NO tiene `reservation_id` ni `customer_id` (fueron eliminados en migración)

#### `reservation_room_guests` ⚠️ **TABLA PIVOTE**
- **Propósito**: Relacionar `reservation_rooms` con `reservation_guests`
- **Estructura actual**:
  ```
  id (PK)
  reservation_room_id (FK → reservation_rooms.id)
  reservation_guest_id (FK → reservation_guests.id)
  created_at, updated_at
  UNIQUE(reservation_room_id, reservation_guest_id)
  ```
- **IMPORTANTE**: Ya NO tiene `customer_id` ni `guest_id` directamente

---

## 🔄 Flujo de Guardado de Huéspedes

### **Paso 1: Creación de Reserva**

```php
// app/Http/Controllers/ReservationController.php::store()
$reservation = Reservation::create([
    'client_id' => $customerId,  // ⭐ Cliente principal
    // ... otros campos
]);
```

**Resultado**: El cliente principal se guarda en `reservations.client_id`

### **Paso 2: Creación de ReservationRoom**

```php
foreach ($roomIds as $roomId) {
    $reservationRoom = ReservationRoom::create([
        'reservation_id' => $reservation->id,
        'room_id' => $roomId,
    ]);
    
    // Asignar huéspedes adicionales a esta habitación
    $this->assignGuestsToRoom(
        $reservationRoom, 
        $normalizedRoomGuests[$roomIdInt] ?? []
    );
}
```

### **Paso 3: Guardado de Huéspedes Adicionales**

```php
// app/Http/Controllers/ReservationController.php::assignGuestsToRoom()

foreach ($validGuestIds as $guestId) {
    // PASO 3.1: Verificar si ya existe en reservation_guests
    $existingReservationGuest = DB::table('reservation_guests')
        ->where('reservation_room_id', $reservationRoom->id)
        ->where('guest_id', $guestId)
        ->first();
    
    if (!$existingReservationGuest) {
        // PASO 3.2: Crear registro en reservation_guests
        $reservationGuestId = DB::table('reservation_guests')->insertGetId([
            'reservation_room_id' => $reservationRoom->id,  // ⭐
            'guest_id' => $guestId,                          // ⭐
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $reservationGuestId = $existingReservationGuest->id;
    }
    
    // PASO 3.3: Verificar si ya existe en reservation_room_guests
    $existingRoomGuest = DB::table('reservation_room_guests')
        ->where('reservation_room_id', $reservationRoom->id)
        ->where('reservation_guest_id', $reservationGuestId)
        ->first();
    
    if (!$existingRoomGuest) {
        // PASO 3.4: Crear registro en reservation_room_guests
        DB::table('reservation_room_guests')->insert([
            'reservation_room_id' => $reservationRoom->id,      // ⭐
            'reservation_guest_id' => $reservationGuestId,      // ⭐
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

**Diagrama del flujo**:
```
customer_id (cliente principal)
    ↓
reservations.client_id ──────────────────────────┐
    ↓                                             │
reservation_rooms.reservation_id                  │
    ↓                                             │
[Para cada guest_id adicional]                    │
    ↓                                             │
reservation_guests                                │
  ├─ reservation_room_id ───────┐                │
  └─ guest_id ───────────────────┼─→ customers.id│
                                 │                │
    reservation_room_guests      │                │
      ├─ reservation_room_id ────┘                │
      └─ reservation_guest_id ────────────────────┘
```

---

## 📖 Flujo de Lectura de Huéspedes

### **Opción 1: Desde ReservationRoom (Huéspedes Adicionales)**

```php
// app/Models/ReservationRoom.php::getGuests()

public function getGuests()
{
    return Customer::query()
        ->whereIn('id', function ($query) {
            // Subquery: obtener guest_id desde reservation_guests
            // pasando por reservation_room_guests
            $query->select('reservation_guests.guest_id')
                ->from('reservation_room_guests')
                ->join('reservation_guests', 
                    'reservation_room_guests.reservation_guest_id', 
                    '=', 
                    'reservation_guests.id'
                )
                ->where('reservation_room_guests.reservation_room_id', $this->id)
                ->whereNotNull('reservation_guests.guest_id');
        })
        ->withTrashed()
        ->get();
}
```

**SQL generado**:
```sql
SELECT * FROM customers
WHERE id IN (
    SELECT reservation_guests.guest_id
    FROM reservation_room_guests
    INNER JOIN reservation_guests 
        ON reservation_room_guests.reservation_guest_id = reservation_guests.id
    WHERE reservation_room_guests.reservation_room_id = ?
      AND reservation_guests.guest_id IS NOT NULL
)
```

### **Opción 2: Desde RoomManager (Todos los Huéspedes)**

```php
// app/Livewire/RoomManager.php::loadRoomGuests()

// 1. Obtener cliente principal
$mainGuest = [
    'id' => $reservation->customer->id,
    'name' => $reservation->customer->name,
    'identification' => $reservation->customer->taxProfile?->identification,
    'phone' => $reservation->customer->phone,
    'email' => $reservation->customer->email,
    'is_main' => true,
];

// 2. Obtener huéspedes adicionales
$additionalGuests = $reservationRoom->getGuests()
    ->map(function($guest) {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'identification' => $guest->taxProfile?->identification,
            'phone' => $guest->phone,
            'email' => $guest->email,
            'is_main' => false,
        ];
    });

// 3. Combinar ambos
$guests = collect([$mainGuest])->merge($additionalGuests);
```

---

## ⚠️ Problemas Identificados

### **Problema 1: `assignGuestsToRoom()` usaba `attach()`**

**Código anterior (NO FUNCIONABA)**:
```php
$reservationRoom->guests()->attach($validGuestIds);
```

**Por qué no funciona**:
- `guests()` retorna un `Builder`, NO una relación Eloquent
- `attach()` solo funciona con relaciones `belongsToMany`
- El código fallaba silenciosamente sin crear registros

**Solución implementada**:
- Insertar manualmente en `reservation_guests` y `reservation_room_guests`
- Verificar duplicados antes de insertar

### **Problema 2: Estructura de BD Compleja**

La estructura actual usa **2 tablas** para guardar huéspedes adicionales:
- `reservation_guests`: Guarda la relación `reservation_room_id` → `guest_id`
- `reservation_room_guests`: Guarda la relación `reservation_room_id` → `reservation_guest_id`

**¿Por qué esta estructura?**
- Parece ser resultado de migraciones evolutivas
- `reservation_guests` fue migrado desde `reservation_id` → `reservation_room_id`
- `reservation_room_guests` fue migrado desde `customer_id` → `reservation_guest_id`

**Ventaja**: Permite reutilizar `reservation_guests` en múltiples habitaciones
**Desventaja**: Aumenta la complejidad del código y las queries

---

## 🔍 Verificación de Datos

### Verificar huéspedes de una habitación:

```sql
-- Ver todos los huéspedes (principal + adicionales) de una habitación
SELECT 
    r.room_number,
    c_main.name AS cliente_principal,
    c_add.name AS huesped_adicional
FROM reservation_rooms rr
INNER JOIN reservations res ON rr.reservation_id = res.id
INNER JOIN customers c_main ON res.client_id = c_main.id
INNER JOIN rooms r ON rr.room_id = r.id
LEFT JOIN reservation_room_guests rrg ON rr.id = rrg.reservation_room_id
LEFT JOIN reservation_guests rg ON rrg.reservation_guest_id = rg.id
LEFT JOIN customers c_add ON rg.guest_id = c_add.id
WHERE r.id = ?;
```

### Verificar si hay datos en las tablas:

```sql
-- Ver reservation_guests de una habitación
SELECT * FROM reservation_guests 
WHERE reservation_room_id IN (
    SELECT id FROM reservation_rooms WHERE reservation_id = ?
);

-- Ver reservation_room_guests de una habitación
SELECT * FROM reservation_room_guests 
WHERE reservation_room_id IN (
    SELECT id FROM reservation_rooms WHERE reservation_id = ?
);
```

---

## 📝 Resumen

### **Guardado**:
1. Cliente principal → `reservations.client_id`
2. Para cada huésped adicional:
   - Crear `reservation_guests` (reservation_room_id, guest_id)
   - Crear `reservation_room_guests` (reservation_room_id, reservation_guest_id)

### **Lectura**:
1. Cliente principal → `$reservation->customer`
2. Huéspedes adicionales → `$reservationRoom->getGuests()`
3. Combinar ambos en un array único

### **Problemas Corregidos**:
- ✅ `assignGuestsToRoom()` ahora inserta manualmente (no usa `attach()`)
- ✅ Verificación de duplicados antes de insertar
- ✅ Logging agregado para debugging

### **Problemas Pendientes**:
- ⚠️ Reservas antiguas pueden no tener huéspedes adicionales guardados (método anterior fallaba)
- ⚠️ Estructura de BD compleja (2 tablas intermedias)

---

## 🧪 Cómo Probar

1. **Crear una nueva reserva con huéspedes adicionales**
2. **Verificar en BD**:
   ```sql
   SELECT COUNT(*) FROM reservation_guests WHERE reservation_room_id = [ID];
   SELECT COUNT(*) FROM reservation_room_guests WHERE reservation_room_id = [ID];
   ```
3. **Abrir modal de huéspedes** y verificar que aparezcan todos

---

## 📌 Notas Importantes

- El cliente principal **NO** se guarda en `reservation_guests`
- Los huéspedes adicionales **SÍ** se guardan en `reservation_guests` y `reservation_room_guests`
- Para obtener TODOS los huéspedes, hay que combinar:
  - `$reservation->customer` (principal)
  - `$reservationRoom->getGuests()` (adicionales)
