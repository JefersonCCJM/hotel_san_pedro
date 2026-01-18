# Modal de Detalle de Habitación - Análisis Técnico Completo

## 📋 Índice

1. [Visión General](#visión-general)
2. [Estructura de Archivos](#estructura-de-archivos)
3. [Flujo de Datos](#flujo-de-datos)
4. [Estructura de Datos (`detailData`)](#estructura-de-datos-detaildata)
5. [Método `openRoomDetail()`](#método-openroomdetail)
6. [Componentes y Secciones del Modal](#componentes-y-secciones-del-modal)
7. [Interacciones y Eventos](#interacciones-y-eventos)
8. [Reglas de Negocio](#reglas-de-negocio)
9. [Single Source of Truth (SSOT)](#single-source-of-truth-ssot)

---

## 🎯 Visión General

El **Modal de Detalle de Habitación** (`components.room-manager.room-detail-modal`) es un componente Blade que muestra información financiera y operativa detallada de una habitación para una fecha específica. Es el centro de gestión de pagos, consumos y estadía para una reserva activa.

### Propósito Principal

- **Visualizar información financiera completa** (hospedaje, abonos, consumos, deuda)
- **Gestionar consumos** (agregar, marcar como pagado, anular pago)
- **Gestionar abonos** (agregar, eliminar)
- **Visualizar historial de pagos** por noche de estadía
- **Registrar devoluciones** cuando hay saldo a favor

### Características Clave

- ✅ **Vista histórica**: Detecta si la fecha consultada es pasada y bloquea modificaciones
- ✅ **Actualización automática**: Se refresca después de registrar pagos/consumos
- ✅ **Integración con modales externos**: Usa el modal de pago (`notifications.payment-modal`) para pagar noches
- ✅ **SSOT financiero**: Usa `payments` como fuente única de verdad para movimientos de dinero

---

## 📁 Estructura de Archivos

```
resources/views/components/room-manager/
├── room-detail-modal.blade.php          # Componente principal del modal
└── scripts.blade.php                    # Scripts JavaScript compartidos (incluye lógica de openRegisterPayment)

app/Livewire/
└── RoomManager.php                      # Controlador Livewire (método openRoomDetail, closeRoomDetail)

resources/views/livewire/
└── room-manager.blade.php               # Vista principal que incluye el modal
```

### Ubicación del Componente

**Archivo principal:**
- `resources/views/components/room-manager/room-detail-modal.blade.php`

**Inclusión en la vista principal:**
```php
// resources/views/livewire/room-manager.blade.php (línea ~87)
<x-room-manager.room-detail-modal 
    :detailData="$detailData" 
    :showAddSale="$showAddSale"
    :showAddDeposit="$showAddDeposit"
/>
```

---

## 🔄 Flujo de Datos

### 1. Apertura del Modal

```
Usuario hace clic en botón "Ver detalle" / "Ver cuenta"
    ↓
Alpine.js / Livewire llama: openRoomDetail($roomId)
    ↓
RoomManager::openRoomDetail($roomId) se ejecuta
    ↓
Se carga habitación con relaciones necesarias
    ↓
Se obtiene reserva activa para fecha seleccionada
    ↓
Se calculan totales financieros (hospedaje, abonos, consumos, deuda)
    ↓
Se construye array $detailData
    ↓
Se establece $this->roomDetailModal = true
    ↓
Blade renderiza el modal con $detailData
```

### 2. Cálculo de Totales Financieros

```php
// En openRoomDetail() (línea ~705-775)

// Obtener reserva activa
$activeReservation = $room->getActiveReservation($this->date);

if ($activeReservation) {
    // Cargar relaciones
    $sales = $activeReservation->sales ?? collect();
    $payments = $activeReservation->payments ?? collect();
    
    // Calcular precio por noche desde ReservationRoom
    $reservationRoom = $room->reservationRooms->first();
    $pricePerNight = (float)($reservationRoom->price_per_night ?? 0);
    
    // Fallback: calcular desde reservation.total_amount
    if ($pricePerNight == 0 && $activeReservation->total_amount && $nights > 0) {
        $pricePerNight = (float)$activeReservation->total_amount / $nights;
    }
    
    // Fallback: usar tarifa base de la habitación
    if ($pricePerNight == 0) {
        $pricePerNight = (float)($room->base_price_per_night ?? 0);
    }
    
    // Calcular total hospedaje
    $totalHospedaje = $pricePerNight * $nights;
    
    // Si totalHospedaje es 0, usar reservation.total_amount (SSOT)
    if ($totalHospedaje == 0) {
        $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
    }
    
    // Calcular abono realizado (suma de pagos positivos - SSOT financiero)
    $abonoRealizado = (float)($payments->sum('amount') ?? 0);
    
    // Calcular consumos
    $salesTotal = (float)($sales->sum('total') ?? 0);
    $salesDebt = (float)($sales->where('is_paid', false)->sum('total') ?? 0);
    
    // Calcular deuda total
    $totalDebt = ($totalHospedaje - $abonoRealizado) + $salesDebt;
}
```

### 3. Estructura del Historial de Estadía (Stay History)

```php
// En openRoomDetail() (línea ~742-761)

// Calcular qué noches están pagadas
$totalPagado = (float)($payments->sum('amount') ?? 0);
$paidAmount = $totalPagado; // Monto disponible para aplicar a las noches

for ($i = 0; $i < $nights; $i++) {
    $currentDate = $checkIn->copy()->addDays($i);
    // Una noche está pagada si el monto pagado cubre al menos el precio de esa noche
    $isPaid = $paidAmount >= $pricePerNight;
    
    $stayHistory[] = [
        'date' => $currentDate->format('Y-m-d'),
        'price' => $pricePerNight,
        'is_paid' => $isPaid,
    ];
    
    // Si la noche está pagada, restar su precio del monto disponible
    if ($isPaid) {
        $paidAmount -= $pricePerNight;
    }
}
```

**Regla de negocio:** Las noches se marcan como "pagadas" en orden cronológico, desde la primera noche, hasta que se agote el monto pagado.

---

## 📊 Estructura de Datos (`detailData`)

El array `$detailData` contiene toda la información que se muestra en el modal:

```php
$this->detailData = [
    // Información de habitación
    'room' => Room,                    // Modelo completo de Room
    
    // Información de reserva
    'reservation' => Reservation,      // Reserva activa (o null si no hay)
    
    // Estado operativo
    'display_status' => RoomDisplayStatus,  // Estado de la habitación (ocupada, libre, etc.)
    
    // Consumos (sales)
    'sales' => [
        [
            'id' => int,
            'product' => ['name' => string],
            'quantity' => int,
            'is_paid' => bool,
            'payment_method' => string|null,
            'total' => float,
        ],
        ...
    ],
    
    // Historial de pagos (positivos)
    'payments_history' => [
        [
            'id' => int,
            'amount' => float,
            'method' => string|null,
            'created_at' => Carbon|null,
        ],
        ...
    ],
    
    // Totales financieros
    'total_hospedaje' => float,        // Total del hospedaje (reservation.total_amount)
    'abono_realizado' => float,        // Suma de pagos positivos (SSOT)
    'sales_total' => float,            // Total de consumos
    'total_debt' => float,             // Deuda total = (hospedaje - abono) + consumos_pendientes
    
    // Identificación del cliente
    'identification' => string|null,   // Identificación del cliente principal
    
    // Historial de estadía (noches)
    'stay_history' => [
        [
            'date' => string,           // 'Y-m-d'
            'price' => float,           // Precio de esa noche
            'is_paid' => bool,          // Si está pagada
        ],
        ...
    ],
    
    // Historial de abonos (igual que payments_history, pero renombrado)
    'deposit_history' => [
        [
            'id' => int,
            'amount' => float,
            'payment_method' => string,
            'notes' => string|null,
            'created_at' => string,     // 'Y-m-d H:i'
        ],
        ...
    ],
    
    // Historial de devoluciones (vacío por ahora, se implementaría desde payments negativos)
    'refunds_history' => [],
    
    // Flags de control
    'is_past_date' => bool,            // Si la fecha consultada es pasada
    'isHistoric' => bool,              // Si es vista histórica (no modificable)
    'canModify' => bool,               // Si se pueden hacer modificaciones
];
```

### Notas Importantes

1. **`abono_realizado`**: Usa `$payments->sum('amount')` directamente, NO `reservation.deposit_amount` (que puede estar desactualizado). Esto es SSOT financiero.

2. **`total_hospedaje`**: Prioriza `reservation.total_amount` (SSOT del hospedaje). Si es 0, calcula desde `price_per_night * nights`.

3. **`stay_history`**: Se calcula dinámicamente desde `price_per_night` y `payments`, NO se persiste en BD.

4. **`deposit_history`**: Es una copia transformada de `payments_history`, pero con formato específico para la UI.

---

## 🔧 Método `openRoomDetail()`

### Ubicación

`app/Livewire/RoomManager.php` (línea ~679-823)

### Firma

```php
public function openRoomDetail($roomId)
```

### Proceso Completo

#### 1. Carga de Habitación con Relaciones

```php
$room = Room::with([
    'reservationRooms' => function($q) {
        $q->where('check_in_date', '<=', $this->date->toDateString())
          ->where('check_out_date', '>=', $this->date->toDateString());
    },
    'reservationRooms.reservation.customer',
    'reservationRooms.reservation.sales.product',
    'reservationRooms.reservation.payments',
    'rates',
    'maintenanceBlocks'
])->find($roomId);
```

**Relaciones cargadas:**
- `reservationRooms`: Filtradas por fecha seleccionada
- `reservation.customer`: Cliente principal
- `reservation.sales.product`: Consumos con productos
- `reservation.payments`: Pagos (SSOT financiero)
- `rates`: Tarifas de la habitación
- `maintenanceBlocks`: Bloqueos de mantenimiento

#### 2. Verificación de Acceso (Vista Histórica)

```php
$availabilityService = $room->getAvailabilityService();
$accessInfo = $availabilityService->getAccessInfo($this->date);

if ($accessInfo['isHistoric']) {
    $this->dispatch('notify', type: 'warning', message: 'Información histórica: datos en solo lectura. No se permite modificar.');
}
```

**Regla:** Si la fecha consultada es anterior a hoy, el modal se muestra en "solo lectura" (`is_past_date = true`, `canModify = false`).

#### 3. Obtención de Reserva Activa

```php
$activeReservation = $room->getActiveReservation($this->date);
```

**Método usado:** `Room::getActiveReservation($date)` - Obtiene la reserva que intersecta con la fecha consultada.

#### 4. Cálculo de Precio por Noche

```php
// Prioridad 1: ReservationRoom.price_per_night
$pricePerNight = (float)($reservationRoom->price_per_night ?? 0);

// Prioridad 2: Calcular desde reservation.total_amount
if ($pricePerNight == 0 && $activeReservation->total_amount && $nights > 0) {
    $pricePerNight = (float)$activeReservation->total_amount / $nights;
}

// Prioridad 3: Usar tarifa de room_rates
if ($pricePerNight == 0 && $room->rates?->isNotEmpty()) {
    $pricePerNight = (float)($room->rates->sortBy('min_guests')->first()->price_per_night ?? 0);
}

// Prioridad 4: Usar base_price_per_night
if ($pricePerNight == 0) {
    $pricePerNight = (float)($room->base_price_per_night ?? 0);
}
```

**SSOT del precio:** `ReservationRoom.price_per_night` es la fuente principal. Si no existe, se calcula desde `reservation.total_amount`.

#### 5. Construcción de `$detailData`

Ver sección ["Estructura de Datos (`detailData`)"](#estructura-de-datos-detaildata) para el formato completo.

---

## 🎨 Componentes y Secciones del Modal

### 1. Header

```blade
<div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <i class="fas fa-door-open"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">Habitación {{ $detailData['room']['room_number'] }}</h3>
            @if(isset($detailData['is_past_date']) && $detailData['is_past_date'])
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-history mr-1"></i> Vista histórica - Solo lectura
                </p>
            @endif
        </div>
    </div>
    <button @click="roomDetailModal = false">✕</button>
</div>
```

### 2. Cards de Resumen Financiero

Cuatro cards que muestran:

1. **Hospedaje**: `$detailData['total_hospedaje']`
2. **Abono**: `$detailData['abono_realizado']` (con botón para editar abono si no es fecha histórica)
3. **Consumos**: `$detailData['sales_total']`
4. **Pendiente/Crédito**: `$detailData['total_debt']`
   - Si `total_debt < 0`: "Se Le Debe al Cliente" (con botón para registrar devolución)
   - Si `total_debt > 0`: "Pendiente" (rojo)

**Ubicación en código:** Línea ~29-68 del modal

### 3. Detalle de Consumos

- **Tabla de consumos**: Muestra productos, cantidades, estado de pago, total
- **Formulario para agregar consumo**: Se muestra cuando `$showAddSale = true`
- **Acciones por consumo**:
  - Si está pagado: Botón "Anular Pago"
  - Si está pendiente: Botón "Marcar Pago"

**Ubicación en código:** Línea ~70-141 del modal

### 4. Historial de Devoluciones (si existe)

Muestra devoluciones registradas (actualmente vacío, pero preparado para implementarse desde `payments` negativos).

**Ubicación en código:** Línea ~143-171 del modal

### 5. Historial de Abonos

- **Tabla de abonos**: Muestra fecha, monto, método de pago, notas
- **Formulario para agregar abono**: Se muestra cuando `$showAddDeposit = true`
- **Acción**: Botón "Eliminar abono" (si no es fecha histórica)

**Ubicación en código:** Línea ~173-258 del modal

### 6. Estado de Pago por Noches

Tabla que muestra cada noche de la estadía:
- **Fecha**
- **Valor noche**
- **Estado**:
  - Si está pagada: Badge "Pagado" (verde)
  - Si está pendiente: Badge "Pendiente" (rojo) + Botón "Pagar noche"

**Acción "Pagar noche":**
- Llama a `window.openRegisterPayment(reservationId, nightPrice, financialContext)`
- Este método está definido en `components/room-manager/scripts.blade.php`
- Abre el modal `notifications.payment-modal` con el contexto financiero

**Ubicación en código:** Línea ~260-318 del modal

---

## 🔌 Interacciones y Eventos

### Eventos Livewire Disparados desde el Modal

1. **`wire:click="toggleAddSale"`**: Muestra/oculta formulario para agregar consumo
2. **`wire:click="addSale"`**: Registra un nuevo consumo
3. **`wire:click="toggleAddDeposit"`**: Muestra/oculta formulario para agregar abono
4. **`wire:click="addDeposit"`**: Registra un nuevo abono

### Eventos Alpine.js / JavaScript

1. **`@click="confirmPaySale($saleId)"`**: Confirma marcar consumo como pagado
2. **`@click="confirmRevertSale($saleId)"`**: Confirma anular pago de consumo
3. **`@click="confirmDeleteDeposit($depositId, $amount, $formattedAmount)"`**: Confirma eliminar abono
4. **`@click="confirmRefund($reservationId, $amount, $formattedAmount)"`**: Confirma registrar devolución
5. **`window.openRegisterPayment(...)`**: Abre modal de pago para pagar noche específica

### Eventos Livewire Recibidos

El modal se refresca automáticamente cuando:
- Se registra un pago (`registerPayment` recarga el modal si está abierto)
- Se agrega un consumo
- Se agrega un abono

**Código de recarga automática** (`app/Livewire/RoomManager.php` línea ~1179-1186):

```php
// Recargar datos del modal si está abierto
if ($this->roomDetailModal && $this->detailData && isset($this->detailData['reservation']['id']) && $this->detailData['reservation']['id'] == $reservationId) {
    // Obtener el room_id desde reservation_rooms
    $reservationRoom = $reservation->reservationRooms()->first();
    if ($reservationRoom && $reservationRoom->room_id) {
        // Forzar recarga del modal con los nuevos datos de pago
        $this->openRoomDetail($reservationRoom->room_id);
    }
}
```

---

## 📐 Reglas de Negocio

### 1. Vista Histórica (Solo Lectura)

- **Condición**: `$this->date->lt(now()->startOfDay())`
- **Comportamiento**: Bloquea todas las acciones de modificación (agregar consumo, agregar abono, marcar pago, etc.)
- **Indicador visual**: Badge "Vista histórica - Solo lectura" en el header

### 2. Cálculo de Deuda Total

```php
$totalDebt = ($totalHospedaje - $abonoRealizado) + $salesDebt;
```

**Fórmula:**
- `totalDebt = (hospedaje - abonos) + consumos_pendientes`
- Si `totalDebt < 0`: Hay crédito/saldo a favor (el hotel debe al cliente)
- Si `totalDebt > 0`: Hay deuda pendiente (el cliente debe al hotel)
- Si `totalDebt = 0`: Cuenta al día

### 3. Estado de Pago por Noches

- Las noches se marcan como "pagadas" en orden cronológico
- Una noche está pagada si el monto total pagado cubre su precio
- Ejemplo: Si se pagó 60.000 y cada noche cuesta 30.000, las primeras 2 noches están pagadas

### 4. SSOT para Abonos

- **Usa:** `$payments->sum('amount')` (suma de pagos positivos en tabla `payments`)
- **NO usa:** `reservation.deposit_amount` (puede estar desactualizado)

### 5. SSOT para Total del Hospedaje

- **Prioridad 1:** `reservation.total_amount` (SSOT absoluto)
- **Prioridad 2:** `ReservationRoom.price_per_night * nights` (cálculo desde tarifa guardada)
- **Prioridad 3:** `Room.rates` (tarifas configuradas)
- **Prioridad 4:** `Room.base_price_per_night` (precio base)

---

## ✅ Single Source of Truth (SSOT)

### Fuentes de Verdad Absolutas

1. **Movimientos de Dinero**: `payments` table
   - Pagos: `amount > 0`
   - Devoluciones: `amount < 0`
   - **NO se usa:** `reservation.deposit_amount` (puede estar desactualizado)

2. **Total del Hospedaje**: `reservation.total_amount`
   - Se define una sola vez al arrendar
   - NO se recalcula durante el release

3. **Estado de Reserva**: `reservation` + `reservation_rooms` + `stays`
   - `stays` determina ocupación real (con timestamps)
   - `reservation_rooms` determina fechas planificadas

4. **Consumos**: `reservation_sales` table
   - Estado de pago: `is_paid`
   - Método de pago: `payment_method`

### Lo que NO es SSOT

1. **`stay_history`**: Se calcula dinámicamente, NO se persiste en BD
2. **`deposit_history`**: Es una transformación de `payments_history`, NO es una tabla separada
3. **`total_debt`**: Se calcula on-the-fly, NO se persiste (aunque `reservation.balance_due` puede tenerlo)

---

## 🔄 Flujo de Actualización

### Después de Registrar un Pago

```
registerPayment() se ejecuta
    ↓
Pago se guarda en tabla payments
    ↓
Se recalcula reservation.balance_due
    ↓
Se verifica si el modal de detalle está abierto
    ↓
Si está abierto Y es la misma reserva:
    Se llama openRoomDetail($roomId) de nuevo
    ↓
El modal se refresca con los nuevos datos
```

### Después de Agregar un Consumo

```
addSale() se ejecuta
    ↓
Consumo se guarda en reservation_sales
    ↓
Se actualiza $this->detailData['sales']
    ↓
El modal se re-renderiza automáticamente (Livewire)
```

---

## 🐛 Casos Especiales y Consideraciones

### 1. Fecha Histórica

- El modal bloquea modificaciones si `is_past_date = true`
- Los botones de acción (agregar, marcar pago, etc.) no se muestran

### 2. Reserva No Encontrada

- Si no hay reserva activa para la fecha consultada:
  - El modal muestra: "No hay reserva activa para esta fecha"
  - Solo se muestra información de la habitación (sin datos financieros)

### 3. Precio por Noche = 0

- Si `price_per_night` es 0 después de todos los fallbacks:
  - `total_hospedaje` usa `reservation.total_amount`
  - Si eso también es 0, se muestra 0 en la UI (puede indicar error de configuración)

### 4. Deuda Negativa (Crédito)

- Si `total_debt < 0`: El sistema muestra "Se Le Debe al Cliente"
- Muestra botón para registrar devolución (si no es fecha histórica)
- **Regla hotelera**: No se puede devolver mientras la habitación esté ocupada (ver `registerCustomerRefund`)

---

## 📝 Métodos Relacionados en RoomManager

### `closeRoomDetail()`

Cierra el modal y limpia `$detailData`.

```php
public function closeRoomDetail()
{
    $this->roomDetailModal = false;
    $this->detailData = null;
}
```

### `toggleAddSale()` / `toggleAddDeposit()`

Muestran/ocultan los formularios para agregar consumo o abono.

```php
public function toggleAddSale(): void
{
    $this->showAddSale = !$this->showAddSale;
}

public function toggleAddDeposit(): void
{
    $this->showAddDeposit = !$this->showAddDeposit;
}
```

### `addSale()` / `addDeposit()`

Registran nuevos consumos o abonos. Ver documentación de `RoomManager` para detalles completos.

---

## 🎯 Resumen Ejecutivo

El **Modal de Detalle de Habitación** es el centro de gestión financiera para una reserva activa. Utiliza `payments` como SSOT para movimientos de dinero y `reservation.total_amount` como SSOT para el total del hospedaje. Soporta vista histórica (solo lectura) y se actualiza automáticamente después de acciones financieras. Proporciona una interfaz completa para gestionar pagos, consumos y visualizar el estado de la cuenta.

**Dependencias críticas:**
- `RoomManager::openRoomDetail()` - Carga y estructura los datos
- `notifications.payment-modal` - Para registrar pagos de noches
- `RoomAvailabilityService` - Para determinar acceso histórico
- Tablas: `payments`, `reservation_sales`, `reservations`, `reservation_rooms`
