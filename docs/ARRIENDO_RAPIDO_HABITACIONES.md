# 📋 Contexto: Arriendo Rápido de Habitaciones (Quick Rent)

## 🎯 Resumen Ejecutivo

Este documento explica cómo funciona el **arriendo rápido** (`quick-rent-modal`) de habitaciones para el día de hoy. Este flujo permite a los recepcionistas arrendar una habitación inmediatamente con check-in al momento de creación.

**Componentes Involucrados:**
- Vista: `resources/views/components/room-manager/quick-rent-modal.blade.php`
- Livewire: `app/Livewire/RoomManager.php` (métodos `openQuickRent`, `storeQuickRent`, `submitQuickRent`)
- Tablas: `reservations`, `reservation_rooms`, `stays`, `payments`

---

## 📊 Tablas de Base de Datos

### 1. `reservations` (Reserva Principal)

**Campos Relevantes para Quick Rent:**
```sql
CREATE TABLE reservations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_code VARCHAR(255) UNIQUE,        -- Ej: RSV-20260114120000-ABCD
    client_id BIGINT,                            -- FK a customers (huésped principal)
    status_id INT,                               -- 1 = pending (default para walk-in)
    total_guests INT,                            -- Total de huéspedes (principal + adicionales)
    adults INT,                                  -- = total_guests (walk-in siempre adultos)
    children INT DEFAULT 0,                      -- Siempre 0 en quick rent
    total_amount DECIMAL(12,2),                  -- Monto total del hospedaje
    deposit_amount DECIMAL(12,2) DEFAULT 0,      -- Abono inicial
    balance_due DECIMAL(12,2),                   -- Saldo pendiente
    payment_status_id INT,                       -- FK a payment_statuses
    source_id INT DEFAULT 1,                     -- 1 = reception/walk_in
    created_by INT,                              -- FK a users (recepcionista)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES customers(id),
    FOREIGN KEY (payment_status_id) REFERENCES payment_statuses(id),
    FOREIGN KEY (source_id) REFERENCES reservation_sources(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

---

### 2. `reservation_rooms` (Habitación Asignada)

**Campos Relevantes:**
```sql
CREATE TABLE reservation_rooms (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_id BIGINT,                       -- FK a reservations
    room_id BIGINT,                              -- FK a rooms
    check_in_date DATE,                          -- Fecha de check-in (para hoy: fecha actual)
    check_out_date DATE,                         -- Fecha de check-out (mañana por defecto)
    nights INT,                                  -- Número de noches (calc: check_out - check_in)
    price_per_night DECIMAL(12,2),               -- Precio por noche (según tarifa)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
```

---

### 3. `stays` (Ocupación Real) ⭐ **CRÍTICO**

**Esta tabla marca la habitación como OCUPADA:**

```sql
CREATE TABLE stays (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_id BIGINT,                       -- FK a reservations
    room_id BIGINT,                              -- FK a rooms
    check_in_at TIMESTAMP,                       -- ✅ Check-in INMEDIATO (now())
    check_out_at TIMESTAMP NULL,                 -- NULL hasta que se libere
    status VARCHAR(50),                          -- 'active' | 'pending_checkout' | 'finished'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
```

**⚠️ IMPORTANTE:**
- **`check_in_at = now()`**: El check-in es INMEDIATO cuando se crea el quick rent
- **`check_out_at = NULL`**: Se completará al liberar la habitación
- **`status = 'active'`**: Marca la habitación como OCUPADA
- Una habitación está **OCUPADA** si hay una `Stay` activa que intersecta la fecha actual

---

### 4. `payments` (Pagos - SSOT Financiero)

**Campos Relevantes:**
```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_id BIGINT,                       -- FK a reservations
    amount DECIMAL(12,2),                        -- Monto (positivo = pago, negativo = devolución)
    payment_method_id INT,                       -- FK a payments_methods
    bank_name VARCHAR(255) NULL,                 -- Solo para transferencia
    reference VARCHAR(255) NULL,                 -- Solo para transferencia
    paid_at TIMESTAMP,                           -- Fecha del pago
    created_by INT,                              -- FK a users
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (payment_method_id) REFERENCES payments_methods(id)
);
```

**Tipos de Pago:**
- **Efectivo**: `payment_method_id` = ID de "Efectivo", `bank_name` = NULL, `reference` = NULL
- **Transferencia**: `payment_method_id` = ID de "Transferencia", `bank_name` y `reference` opcionales

---

## 🔄 Flujo Completo de Arriendo Rápido

### **Paso 1: Usuario Abre el Modal (`openQuickRent`)**

**Trigger:** Click en botón "Arrendar" de una habitación disponible.

**Código (`RoomManager::openQuickRent($roomId)`):**
```php
public function openQuickRent($roomId)
{
    $room = Room::with('rates')->find($roomId);
    
    // Calcular precio base desde room_rates o base_price_per_night
    $basePrice = 0;
    if ($room->rates && $room->rates->isNotEmpty()) {
        $firstRate = $room->rates->sortBy('min_guests')->first();
        $basePrice = $firstRate->price_per_night ?? 0;
    }
    if ($basePrice == 0 && $room->base_price_per_night) {
        $basePrice = $room->base_price_per_night;
    }
    
    // Inicializar formulario
    $this->rentForm = [
        'room_id' => $roomId,
        'room_number' => $room->room_number,
        'check_in_date' => $this->date->toDateString(),      // ✅ HOY
        'check_out_date' => $this->date->copy()->addDay()->toDateString(), // ✅ MAÑANA (default)
        'client_id' => null,                                  // Pendiente de seleccionar
        'guests_count' => 1,                                  // Solo huésped principal (inicial)
        'max_capacity' => $room->max_capacity,
        'total' => $basePrice,                                // Precio por 1 noche
        'deposit' => 0,                                       // Sin abono inicial
        'payment_method' => 'efectivo',                       // Default
        'bank_name' => '',                                    // Para transferencia
        'reference' => '',                                    // Para transferencia
    ];
    
    $this->additionalGuests = [];                             // Sin huéspedes adicionales inicialmente
    $this->quickRentModal = true;
    $this->dispatch('quickRentOpened');
    $this->recalculateQuickRentTotals($room);
}
```

---

### **Paso 2: Usuario Completa el Formulario**

**Campos del Modal:**
1. **Huésped Principal**: Selector con búsqueda (TomSelect)
2. **Personas**: Contador automático (principal + adicionales) / Capacidad máxima
3. **Check-Out**: Input date (mínimo = mañana)
4. **Huéspedes Adicionales**: Lista opcional (agregar/remover)
5. **Resumen Financiero**:
   - Total Hospedaje (calculado automáticamente)
   - Abono Recibido (editable)
   - Saldo Pendiente (calculado)
   - Método de Pago (efectivo/transferencia)

**Cálculo Automático de Total:**
```php
private function recalculateQuickRentTotals(?Room $room = null): void
{
    $roomModel = $room ?? Room::with('rates')->find($this->rentForm['room_id']);
    
    // Calcular total de huéspedes
    $guests = $this->calculateGuestCount();  // principal + adicionales
    
    // Calcular noches
    $checkIn = Carbon::parse($this->rentForm['check_in_date']);
    $checkOut = Carbon::parse($this->rentForm['check_out_date']);
    $nights = max(1, $checkIn->diffInDays($checkOut));
    
    // Obtener precio por noche según cantidad de huéspedes
    $pricePerNight = $this->findRateForGuests($roomModel, $guests);
    $total = $pricePerNight * $nights;
    
    $this->rentForm['guests_count'] = $guests;
    $this->rentForm['total'] = $total;
}
```

**Selección de Tarifa:**
```php
private function findRateForGuests(Room $room, int $guests): float
{
    // Buscar tarifa que coincida con cantidad de huéspedes
    $rates = $room->rates;
    $matching = $rates->first(function ($rate) use ($guests) {
        $min = (int)($rate->min_guests ?? 0);
        $max = (int)($rate->max_guests ?? 0);
        return $guests >= $min && ($max === 0 || $guests <= $max);
    });
    
    if ($matching) {
        return (float)($matching->price_per_night ?? 0);
    }
    
    // Fallback: base_price_per_night
    return (float)($room->base_price_per_night ?? 0);
}
```

---

### **Paso 3: Usuario Confirma el Arriendo (`storeQuickRent`)**

**Trigger:** Click en botón "Confirmar Arrendamiento".

**Método Principal:** `RoomManager::submitQuickRent()` (alias: `storeQuickRent()`)

**Flujo Completo:**

#### **3.1 Validaciones Iniciales**

```php
// Bloquear fechas históricas
if (Carbon::parse($this->rentForm['check_in_date'])->lt(Carbon::today())) {
    throw new \RuntimeException('No se pueden crear reservas en fechas históricas.');
}

// Calcular totales
$guests = $this->calculateGuestCount();        // principal + adicionales
$checkIn = Carbon::parse($validated['check_in_date']);
$checkOut = Carbon::parse($validated['check_out_date']);
$nights = max(1, $checkIn->diffInDays($checkOut));
$pricePerNight = $this->findRateForGuests($room, $guests);
$totalAmount = $pricePerNight * $nights;
$depositAmount = (float)($this->rentForm['deposit'] ?? 0);
$balanceDue = $totalAmount - $depositAmount;
```

#### **3.2 Crear Reserva (`reservations`)**

```php
$reservationCode = sprintf('RSV-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));

$reservation = Reservation::create([
    'reservation_code' => $reservationCode,    // Ej: RSV-20260114120000-ABCD
    'client_id' => $validated['client_id'],    // Huésped principal
    'status_id' => 1,                          // pending (walk-in)
    'total_guests' => $validated['guests_count'], // Total de huéspedes
    'adults' => $validated['guests_count'],    // Todos adultos
    'children' => 0,                           // Siempre 0
    'total_amount' => $totalAmount,
    'deposit_amount' => $depositAmount,        // Abono inicial
    'balance_due' => $balanceDue,              // Saldo pendiente
    'payment_status_id' => $paymentStatusId,   // 'paid' | 'partial' | 'pending'
    'source_id' => 1,                          // reception/walk_in
    'created_by' => auth()->id(),              // Recepcionista actual
]);
```

#### **3.3 Registrar Pago de Transferencia (Opcional)**

**Solo si `payment_method === 'transferencia'` y hay abono:**

```php
if ($paymentMethod === 'transferencia' && ($depositAmount > 0 || $referencePayload)) {
    DB::table('payments')->insert([
        'reservation_id' => $reservation->id,
        'amount' => $depositAmount > 0 ? $depositAmount : 0,
        'payment_method_id' => $this->getPaymentMethodId('transferencia'),
        'bank_name' => $bankName ?: null,
        'reference' => $referencePayload,       // Formato: "REF123 | Banco: Bancolombia"
        'paid_at' => now(),
        'created_by' => auth()->id(),
    ]);
}
```

**⚠️ NOTA:** Si el método es "efectivo", el pago NO se registra aquí. Se registra después usando `registerPayment()` desde el modal de pagos.

#### **3.4 Crear ReservationRoom (`reservation_rooms`)**

```php
ReservationRoom::create([
    'reservation_id' => $reservation->id,
    'room_id' => $validated['room_id'],
    'check_in_date' => $validated['check_in_date'],      // ✅ HOY
    'check_out_date' => $validated['check_out_date'],    // ✅ MAÑANA (o la fecha seleccionada)
    'nights' => $nights,
    'price_per_night' => $pricePerNight,
]);
```

#### **3.5 Crear Stay (`stays`) ⭐ **CRÍTICO****

**Esta es la acción que marca la habitación como OCUPADA:**

```php
$stay = \App\Models\Stay::create([
    'reservation_id' => $reservation->id,
    'room_id' => $validated['room_id'],
    'check_in_at' => now(),                     // ✅ Check-in INMEDIATO (timestamp)
    'check_out_at' => null,                     // ✅ NULL hasta que se libere
    'status' => 'active',                       // ✅ 'active' = habitación OCUPADA
]);
```

**Por qué es crítico:**
- Una habitación está **OCUPADA** si existe una `Stay` activa (`status = 'active'`) que intersecta la fecha actual
- `check_in_at = now()` hace que la ocupación sea inmediata
- `check_out_at = NULL` indica que la habitación aún no ha sido liberada

#### **3.6 Invalidar Relación en Memoria (Optimización)**

```php
$room = Room::find($validated['room_id']);
if ($room) {
    $room->unsetRelation('stays');  // Forzar recarga de relación en próximas consultas
}
```

#### **3.7 Cerrar Modal y Refrescar UI**

```php
$this->dispatch('notify', type: 'success', message: 'Arriendo registrado exitosamente. Habitación ocupada.');
$this->closeQuickRent();
$this->resetPage();
$this->dispatch('room-view-changed', date: $this->date->toDateString());
```

---

## ⚠️ **LIMITACIÓN ACTUAL: Huéspedes Adicionales**

**Problema Detectado:**
El método `submitQuickRent()` **NO guarda** los huéspedes adicionales en `reservation_guests` y `reservation_room_guests`.

**Estado Actual:**
- Los huéspedes adicionales se pueden **agregar** al array `$this->additionalGuests` en el formulario
- Pero **NO se persisten** en la base de datos al confirmar el arriendo
- Solo se guarda el `client_id` (huésped principal) en `reservations.client_id`

**Solución Futura:**
Implementar `assignGuestsToRoom()` similar a `ReservationController::assignGuestsToRoom()` después de crear `ReservationRoom`:

```php
// DESPUÉS de crear ReservationRoom
$reservationRoom = ReservationRoom::create([...]);

// Guardar huéspedes adicionales
if (!empty($this->additionalGuests) && is_array($this->additionalGuests)) {
    $additionalGuestIds = array_column($this->additionalGuests, 'customer_id');
    $this->assignGuestsToRoom($reservationRoom, $additionalGuestIds);
}
```

**Ver documentación:** `docs/ANALISIS_HUESPEDES.md` para entender la estructura de `reservation_guests` y `reservation_room_guests`.

---

## 📝 Ejemplo Completo de Flujo

### **Input del Usuario:**
```
Habitación: 101
Huésped Principal: Juan Pérez (ID: 5)
Check-Out: 2026-01-15 (mañana)
Huéspedes Adicionales: María García (ID: 8), Carlos López (ID: 12)
Total: $80,000 (3 huéspedes × 1 noche × $80,000/noche)
Abono: $50,000
Método: Efectivo
```

### **Resultado en Base de Datos:**

**Tabla `reservations`:**
```
id: 10
reservation_code: "RSV-20260114120000-ABCD"
client_id: 5                    // ✅ Juan Pérez (principal)
status_id: 1                    // pending
total_guests: 3                 // ✅ 1 principal + 2 adicionales
adults: 3
children: 0
total_amount: 80000
deposit_amount: 50000
balance_due: 30000
payment_status_id: 2            // partial (hay abono pero no completo)
source_id: 1                    // walk_in
created_by: 1                   // Recepcionista
created_at: "2026-01-14 12:00:00"
```

**Tabla `reservation_rooms`:**
```
id: 15
reservation_id: 10
room_id: 101
check_in_date: "2026-01-14"     // ✅ HOY
check_out_date: "2026-01-15"    // ✅ MAÑANA
nights: 1
price_per_night: 80000
created_at: "2026-01-14 12:00:00"
```

**Tabla `stays`:**
```
id: 20
reservation_id: 10
room_id: 101
check_in_at: "2026-01-14 12:00:00"  // ✅ Check-in INMEDIATO
check_out_at: NULL                   // ✅ NULL hasta liberar
status: "active"                     // ✅ OCUPADA
created_at: "2026-01-14 12:00:00"
```

**Tabla `payments`:**
```
(Si método = efectivo, NO se registra aquí. Se registra después desde modal de pagos.)

(Si método = transferencia Y hay abono):
id: 25
reservation_id: 10
amount: 50000
payment_method_id: 2            // Transferencia
bank_name: "Bancolombia"        // Opcional
reference: "REF123456"          // Opcional
paid_at: "2026-01-14 12:00:00"
created_by: 1
```

**Tabla `reservation_guests` y `reservation_room_guests`:**
```
❌ NO SE CREAN (limitación actual)
(Solo se guarda client_id en reservations.client_id)
```

---

## 🔗 Relaciones Eloquent

### **Reservation Model**
```php
public function customer()              // belongsTo(Customer::class) - Huésped principal
public function reservationRooms()     // hasMany(ReservationRoom::class)
public function stays()                 // hasMany(Stay::class)
public function payments()              // hasMany(Payment::class)
public function sales()                 // hasMany(Sale::class)
```

### **Stay Model**
```php
public function reservation()           // belongsTo(Reservation::class)
public function room()                  // belongsTo(Room::class)
```

### **ReservationRoom Model**
```php
public function reservation()           // belongsTo(Reservation::class)
public function room()                  // belongsTo(Room::class)
public function guests()                // Query Builder personalizado (NO Eloquent relation)
public function getGuests()             // Helper que retorna Collection de Customer
```

### **Room Model**
```php
public function stays()                 // hasMany(Stay::class) - OCUPACIONES REALES
public function reservationRooms()     // hasMany(ReservationRoom::class)
public function getActiveReservation() // Obtiene reserva activa vía Stay
```

---

## 🎯 Reglas de Negocio

### **1. Check-In Inmediato**
- El `check_in_at` se establece en `now()` al crear el arriendo rápido
- No hay proceso separado de "check-in" después de crear la reserva
- La habitación queda **OCUPADA** inmediatamente

### **2. Check-Out por Defecto**
- El `check_out_date` por defecto es **mañana** (`date + 1 día`)
- El usuario puede cambiarlo en el formulario
- El `check_out_at` se completa al liberar la habitación

### **3. Cálculo de Precios**
- El precio por noche se calcula según la **cantidad de huéspedes** (no según la habitación)
- Se busca en `room_rates` el rango que contenga el número de huéspedes
- Fallback a `base_price_per_night` si no hay tarifa específica

### **4. Métodos de Pago**
- **Efectivo**: No se registra pago automáticamente. Se debe registrar después desde el modal de pagos.
- **Transferencia**: Si hay abono, se registra automáticamente en `payments` con `bank_name` y `reference` (opcionales)

### **5. Estado de Pago**
- **`paid`**: Si `deposit >= total` (pago completo)
- **`partial`**: Si `deposit > 0` pero `deposit < total` (pago parcial)
- **`pending`**: Si `deposit = 0` (sin abono)

### **6. Bloqueo de Fechas Históricas**
- No se puede crear un arriendo rápido en fechas pasadas
- Validación: `check_in_date >= today()`

---

## 🔍 Verificación Post-Creación

### **¿Cómo Verificar que el Arriendo Fue Exitoso?**

1. **Habitación Aparece como OCUPADA:**
   ```php
   $room = Room::find($roomId);
   $isOccupied = $room->isOccupied();  // ✅ true
   ```

2. **Existe Stay Activa:**
   ```php
   $stay = Stay::where('room_id', $roomId)
       ->where('status', 'active')
       ->whereNull('check_out_at')
       ->first();
   // ✅ $stay !== null
   ```

3. **Reserva con Source = walk_in:**
   ```php
   $reservation = Reservation::where('id', $reservationId)
       ->where('source_id', 1)  // walk_in
       ->first();
   // ✅ $reservation !== null
   ```

4. **ReservationRoom Creado:**
   ```php
   $reservationRoom = ReservationRoom::where('reservation_id', $reservationId)
       ->where('room_id', $roomId)
       ->first();
   // ✅ $reservationRoom !== null
   ```

---

## 📚 Referencias

- **Componente Livewire**: `app/Livewire/RoomManager.php`
- **Modelo Reservation**: `app/Models/Reservation.php`
- **Modelo Stay**: `app/Models/Stay.php`
- **Modelo ReservationRoom**: `app/Models/ReservationRoom.php`
- **Vista Modal**: `resources/views/components/room-manager/quick-rent-modal.blade.php`
- **Análisis de Huéspedes**: `docs/ANALISIS_HUESPEDES.md`

---

**Última actualización:** 2026-01-14
