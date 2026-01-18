# 📋 Contexto: Liberación de Habitaciones (Room Release)

## 🎯 Resumen Ejecutivo

Este documento explica el flujo completo de **liberación de habitaciones** desde el modal de confirmación (`room-release-confirmation-modal`) hasta la persistencia en base de datos.

**Componentes Involucrados:**
- Vista: `resources/views/components/room-manager/room-release-confirmation-modal.blade.php`
- Livewire: `app/Livewire/RoomManager.php` (métodos `loadRoomReleaseData`, `releaseRoom`, `registerCustomerRefund`)
- Modelo: `app/Models/RoomReleaseHistory.php`
- Tablas: `stays`, `reservations`, `payments`, `room_release_history`

---

## 📊 Tablas de Base de Datos

### 1. `stays` (Ocupación Real) ⭐ **CRÍTICO**

**Esta tabla marca si una habitación está OCUPADA:**

```sql
CREATE TABLE stays (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_id BIGINT,                       -- FK a reservations
    room_id BIGINT,                              -- FK a rooms
    check_in_at TIMESTAMP,                       -- Check-in (timestamp)
    check_out_at TIMESTAMP NULL,                 -- Check-out (NULL hasta liberar)
    status VARCHAR(50),                          -- 'active' | 'pending_checkout' | 'finished'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
```

**Estados:**
- `active`: Habitación ocupada (check_out_at = NULL)
- `pending_checkout`: Pendiente de checkout
- `finished`: Habitación liberada (check_out_at IS NOT NULL)

---

### 2. `room_release_history` (Historial de Liberaciones)

**Registra un snapshot completo de cada liberación:**

```sql
CREATE TABLE room_release_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    room_id BIGINT,                              -- FK a rooms
    reservation_id BIGINT,                       -- FK a reservations
    customer_id BIGINT,                          -- FK a customers (huésped principal)
    released_by INT,                             -- FK a users (recepcionista que liberó)
    room_number VARCHAR(255),
    
    -- Datos financieros
    total_amount DECIMAL(12,2),                  -- Total del hospedaje
    deposit DECIMAL(12,2),                       -- Total abonado
    consumptions_total DECIMAL(12,2),            -- Total consumos
    pending_amount DECIMAL(12,2),                -- Saldo pendiente al liberar
    
    -- Datos de ocupación
    guests_count INT,                            -- Total de huéspedes
    check_in_date DATE,                          -- Fecha de check-in
    check_out_date DATE,                         -- Fecha de check-out (planeada)
    release_date DATE,                           -- Fecha de liberación (real)
    target_status VARCHAR(50),                   -- 'libre' | 'limpia' | 'pendiente_aseo'
    
    -- Datos del cliente (denormalizados para auditoría)
    customer_name VARCHAR(255),
    customer_identification VARCHAR(255),
    customer_phone VARCHAR(255),
    customer_email VARCHAR(255),
    
    -- Snapshots JSON (datos históricos inmutables)
    reservation_data JSON,                       -- Snapshot completo de la reserva
    sales_data JSON,                             -- Array de consumos
    deposits_data JSON,                          -- Array de pagos
    guests_data JSON,                            -- Array de huéspedes (principal + adicionales)
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (released_by) REFERENCES users(id)
);
```

**⚠️ IMPORTANTE:**
- Los campos JSON (`reservation_data`, `sales_data`, `deposits_data`, `guests_data`) almacenan **snapshots inmutables** de cómo estaba la reserva al momento de liberar
- Estos datos **NO cambian** aunque se modifiquen las tablas originales
- Permite auditoría histórica completa

---

### 3. `reservations` (Actualización de Estado)

**Campos que se actualizan al liberar:**
```sql
balance_due = 0                                    -- ✅ Saldo saldado
payment_status_id = ID de 'paid'                  -- ✅ Estado: pagado
```

---

## 🔄 Flujo Completo de Liberación

### **Paso 1: Usuario Solicita Liberar Habitación**

**Trigger:** Click en botón "Liberar" del menú de acciones de la habitación.

**Evento Alpine.js:**
```javascript
window.dispatchEvent(new CustomEvent('open-release-confirmation', {
    detail: loadRoomReleaseData(roomId)
}));
```

---

### **Paso 2: Cargar Datos para el Modal (`loadRoomReleaseData`)**

**Método:** `RoomManager::loadRoomReleaseData($roomId, $isCancellation = false)`

**Qué hace:**
1. Obtiene la reserva activa de la habitación
2. Calcula resumen financiero:
   - `total_hospedaje`: `reservation.total_amount`
   - `abono_realizado`: `reservation.deposit_amount` (o suma de `payments`)
   - `sales_total`: Suma de todos los consumos
   - `total_debt`: `(total_hospedaje - abono_realizado) + sales_debt`
3. Carga consumos (`sales`)
4. Carga historial de pagos (`payments`)
5. Retorna array con todos los datos para el modal

**Estructura de Respuesta:**
```php
return [
    'room_id' => $room->id,
    'room_number' => $room->room_number,
    'reservation' => $activeReservation->toArray(),
    'sales' => [...],                          // Array de consumos
    'payments_history' => [...],               // Array de pagos
    'refunds_history' => [],                   // Array de devoluciones (futuro)
    'total_hospedaje' => $totalHospedaje,
    'abono_realizado' => $abonoRealizado,
    'sales_total' => $salesTotal,
    'total_debt' => $totalDebt,                // ⚠️ Positivo = debe, Negativo = se le debe
    'identification' => $identification,
    'is_cancellation' => $isCancellation,
];
```

---

### **Paso 3: Usuario Revisa el Modal**

**El Modal Muestra:**

1. **Información del Cliente:**
   - Nombre
   - Identificación

2. **Resumen Financiero:**
   - Total Hospedaje
   - Abono Realizado
   - Total Consumos
   - Deuda Pendiente / Saldo a Favor / Al Día

3. **Consumos:**
   - Lista de productos consumidos
   - Estado de pago de cada consumo

4. **Historial de Abonos:**
   - Lista de pagos registrados

5. **Validaciones Según Estado Financiero:**

   **A) Si hay deuda (`total_debt > 0`):**
   - ⚠️ Bloquea liberación hasta confirmar pago
   - Solicita método de pago (efectivo/transferencia)
   - Si es transferencia: solicita `bank_name` y `reference`
   - Checkbox: "Confirmo que se realizó el pago"

   **B) Si hay saldo a favor (`total_debt < 0`):**
   - ⚠️ Bloquea liberación hasta registrar devolución
   - Botón: "Registrar Devolución de Dinero"
   - Llama a `registerCustomerRefund()`
   - Recarga datos del modal después de devolución

   **C) Si está al día (`total_debt = 0`):**
   - ✅ Permite liberación inmediata
   - Muestra mensaje verde: "Puede proceder a liberar la habitación"

---

### **Paso 4: Usuario Confirma Liberación (`releaseRoom`)**

**Trigger:** Click en botón "Confirmar Liberación" del modal.

**Método:** `RoomManager::releaseRoom($roomId, $status, $paymentMethod, $bankName, $reference)`

**Flujo Completo:**

#### **4.1 Validaciones Iniciales**

```php
// Bloquear fechas históricas
if ($availabilityService->isHistoricDate($today)) {
    throw new \RuntimeException('No se pueden hacer cambios en fechas históricas.');
}

// Obtener stay activa
$activeStay = $availabilityService->getStayForDate($today);
if (!$activeStay) {
    // No hay ocupación para liberar
    return;
}
```

#### **4.2 Obtener Reserva y Calcular Deuda**

```php
$reservation = $activeStay->reservation;
$paymentsTotal = (float)($reservation->payments->sum('amount') ?? 0);
$salesDebt = (float)($reservation->sales->where('is_paid', false)->sum('total') ?? 0);
$balanceDue = (float)($reservation->total_amount ?? 0) - $paymentsTotal + $salesDebt;
```

#### **4.3 Registrar Pago si Hay Deuda**

**Solo si `balanceDue > 0` y `paymentMethod` está presente:**

```php
if ($balanceDue > 0) {
    $paymentMethodId = $this->getPaymentMethodId($paymentMethod);
    
    Payment::create([
        'reservation_id' => $reservation->id,
        'amount' => $balanceDue,                   // ✅ Monto exacto de la deuda
        'payment_method_id' => $paymentMethodId,
        'bank_name' => $paymentMethod === 'transferencia' ? $bankName : null,
        'reference' => $paymentMethod === 'transferencia' ? $reference : 'Pago confirmado en checkout',
        'paid_at' => now(),
        'created_by' => auth()->id(),
    ]);
    
    $balanceDue = 0;  // ✅ Después del pago, balance = 0
}
```

#### **4.4 Validar Balance = 0**

```php
if ($balanceDue != 0) {
    throw new \RuntimeException("No se puede liberar. Deuda pendiente: \${$balanceDue}");
}
```

#### **4.5 Cerrar la STAY ⭐ **CRÍTICO****

**Esta acción libera la habitación:**

```php
$activeStay->update([
    'check_out_at' => now(),                      // ✅ Timestamp de checkout
    'status' => 'finished',                       // ✅ Estado: finalizada
]);
```

**Por qué es crítico:**
- `check_out_at` marca el momento exacto del checkout
- `status = 'finished'` indica que la stay terminó
- La habitación deja de estar **OCUPADA** inmediatamente
- Las consultas `Room::isOccupied()` ahora retornan `false`

#### **4.6 Actualizar Estado de la Reserva**

```php
$reservation->update([
    'balance_due' => 0,
    'payment_status_id' => $paymentStatusId,      // 'paid'
]);
```

#### **4.7 Crear Registro en Historial (`room_release_history`)**

**Snapshot completo para auditoría:**

```php
RoomReleaseHistory::create([
    // IDs básicos
    'room_id' => $room->id,
    'reservation_id' => $reservation->id,
    'customer_id' => $reservation->customer_id,
    'released_by' => auth()->id(),
    
    // Datos financieros calculados
    'total_amount' => $totalAmount,
    'deposit' => $paymentsTotal,
    'consumptions_total' => $consumptionsTotal,
    'pending_amount' => $pendingAmount,           // Deuda pendiente al liberar
    
    // Datos de ocupación
    'guests_count' => $reservation->total_guests,
    'check_in_date' => $checkInDate->toDateString(),
    'check_out_date' => $checkOutDate->toDateString(),
    'release_date' => $today->toDateString(),     // ✅ Fecha de liberación real
    'target_status' => $targetStatus,             // 'libre' | 'limpia' | 'pendiente_aseo'
    
    // Datos del cliente (denormalizados)
    'customer_name' => $reservation->customer->name,
    'customer_identification' => $reservation->customer->taxProfile?->identification,
    'customer_phone' => $reservation->customer->phone,
    'customer_email' => $reservation->customer->email,
    
    // Snapshots JSON (inmutables)
    'reservation_data' => [...],                  // Snapshot completo de reservation
    'sales_data' => [...],                        // Array de consumos
    'deposits_data' => [...],                     // Array de pagos
    'guests_data' => [...],                       // Array de huéspedes (principal + adicionales)
]);
```

**⚠️ IMPORTANTE:**
- Los snapshots JSON son **inmutables** (no cambian aunque se modifiquen las tablas originales)
- `guests_data` incluye:
  - Huésped principal (`is_main = true`) desde `reservations.client_id`
  - Huéspedes adicionales (`is_main = false`) desde `reservation_guests`

#### **4.8 Cerrar Modal y Refrescar UI**

```php
$this->dispatch('room-release-finished', roomId: $roomId);
$this->closeRoomReleaseConfirmation();
$this->dispatch('refreshRooms');                  // ✅ Refresca lista de habitaciones
```

---

## 🔄 Casos Especiales

### **Caso 1: Deuda Pendiente**

**Flujo:**
1. Modal muestra advertencia roja: "¡Atención! La habitación tiene deuda pendiente"
2. Usuario selecciona método de pago (obligatorio)
3. Si transferencia: completa `bank_name` y `reference` (opcionales)
4. Checkbox: "Confirmo que se realizó el pago"
5. Al confirmar:
   - `releaseRoom()` registra pago automáticamente
   - `balanceDue` queda en 0
   - Continúa con liberación normal

---

### **Caso 2: Saldo a Favor (Devolución Requerida)**

**Flujo:**
1. Modal muestra advertencia naranja: "¡Atención! Se le debe dinero al cliente"
2. Botón: "Registrar Devolución de Dinero"
3. Usuario hace click → `registerCustomerRefund()`
4. Se crea `Payment` con `amount` **NEGATIVO**
5. Modal se recarga automáticamente (`loadRoomReleaseData()`)
6. Ahora muestra mensaje verde: "La devolución ha sido registrada"
7. Usuario puede confirmar liberación

**Lógica de Devolución:**
```php
// Calcular overpaid = totalPaid - totalAmount
$totalPaid = SUM(payments donde amount > 0);
$overpaid = $totalPaid - $totalAmount;

// Solo permite devolución si overpaid > 0
if ($overpaid <= 0) {
    throw new DomainException('No hay saldo a favor para devolver.');
}

// Crear pago negativo
Payment::create([
    'amount' => -$amount,                         // ✅ NEGATIVO para devolución
    'payment_method_id' => $paymentMethodId,
    ...
]);
```

---

### **Caso 3: Cuenta al Día**

**Flujo:**
1. Modal muestra mensaje verde: "La habitación está al día"
2. No requiere validaciones adicionales
3. Usuario confirma liberación inmediatamente
4. `releaseRoom()` ejecuta sin pasos financieros adicionales

---

## 📝 Estructura de `guests_data` en Historial

**Array JSON en `room_release_history.guests_data`:**

```json
[
    {
        "id": 5,
        "name": "Juan Pérez",
        "identification": "1234567890",
        "is_main": true
    },
    {
        "id": 8,
        "name": "María García",
        "identification": "0987654321",
        "is_main": false
    },
    {
        "id": 12,
        "name": "Carlos López",
        "identification": "1122334455",
        "is_main": false
    }
]
```

**Fuentes:**
- `is_main = true`: Viene de `reservations.client_id`
- `is_main = false`: Viene de `reservation_guests` → `reservation_room_guests`

---

## 🔍 Validaciones del Modal (Frontend)

**El botón "Confirmar Liberación" está deshabilitado si:**

```javascript
// Caso 1: Hay deuda Y (no confirmó pago O no seleccionó método)
(total_debt > 0) && (!paymentConfirmed || !paymentMethod || (paymentMethod === 'transferencia' && !reference))

// Caso 2: Hay saldo a favor Y no se registró devolución
(total_debt < 0) && (!refunds_history || refunds_history.length === 0)

// Caso 3: Cargando (isLoading)
isLoading
```

---

## 📚 Referencias

- **Vista Modal**: `resources/views/components/room-manager/room-release-confirmation-modal.blade.php`
- **Método Cargar Datos**: `app/Livewire/RoomManager.php::loadRoomReleaseData()`
- **Método Liberar**: `app/Livewire/RoomManager.php::releaseRoom()`
- **Método Devolución**: `app/Livewire/RoomManager.php::registerCustomerRefund()`
- **Modelo Historial**: `app/Models/RoomReleaseHistory.php`
- **Migración Historial**: `database/migrations/2026_01_13_211923_recreate_room_release_history_table.php`

---

**Última actualización:** 2026-01-14
