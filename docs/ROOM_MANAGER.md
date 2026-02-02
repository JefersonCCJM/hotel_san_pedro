# Documento Técnico: Room Manager

## 1️⃣ Visión General del Módulo

### ¿Qué es RoomManager?
RoomManager es el componente Livewire central que gestiona la operación diaria del hotel, sirviendo como el cerebro del sistema de gestión de habitaciones. Es el controlador de dominio que coordina la interacción entre el estado operativo de las habitaciones, las estadías reales, los cobros por noches y el flujo financiero.

### ¿Qué problema resuelve?
- **Gestión operativa**: Control del estado real de las habitaciones (libre, ocupada, pendiente limpieza, mantenimiento)
- **Cobro por noches**: Implementación del sistema de facturación basado en estadías reales y noches individuales
- **Flujo hotelero**: Coordinación del ciclo completo desde check-in hasta liberación
- **Separación de responsabilidades**: Distinción clara entre reservas (planificación) y estadías (ocupación real)

### ¿Qué NO debe hacer?
- **NO gestionar reservas futuras**: Eso es responsabilidad de ReservationManager
- **NO calcular totales desde reservations.total_amount**: Usar stay_nights como SSOT
- **NO liberar habitaciones con saldo pendiente**: Requiere validación financiera completa
- **NO generar noches para fechas futuras**: Protección contra errores temporales

### Diferencia entre vista operativa vs financiera
- **Vista operativa**: Estado de habitaciones, limpieza, ocupación real
- **Vista financiera**: Pagos, noches cobradas, saldo pendiente, devoluciones

---

## 2️⃣ Arquitectura del Módulo

### Livewire como Controlador de Dominio
RoomManager actúa como el orquestador principal que:

```php
class RoomManager extends Component
{
    // Estado de la interfaz
    public string $activeTab = 'rooms';
    public $currentDate = null;
    public $date = null;
    
    // Modales y datos
    public bool $roomDetailModal = false;
    public ?array $detailData = null;
}
```

### Relación con Modelos Principales

#### Room (Habitación)
- **Estado operacional**: `getDisplayStatus()`, `getOperationalStatus()`
- **Disponibilidad**: `getAvailabilityService()`
- **Relaciones**: `stays`, `reservationRooms`, `rates`

#### Reservation (Reserva)
- **Planificación**: Fechas futuras, cliente, totales estimados
- **Relaciones**: `customer`, `payments`, `sales`
- **Fallback**: `total_amount` solo si no hay stay_nights

#### ReservationRoom (Relación Reserva-Habitación)
- **Fechas**: `check_in_date`, `check_out_date`
- **Huéspedes**: `getGuests()` para adicionales
- **Vínculo**: Conecta Reservation → Room → Stay

#### Stay (Estadía Real)
- **Ocupación**: `check_in_at`, `check_out_at` (timestamps)
- **Estado**: `active`, `pending_checkout`, `finished`
- **Diferencia clave**: Representa ocupación REAL, no planificación

#### StayNight (Noche Cobrable)
- **SSOT financiero**: Cada noche = 1 registro con precio y pago
- **Estructura**: `stay_id`, `date`, `price`, `is_paid`
- **Reglas**: Una noche por fecha, checkout no se cobra

#### Payment (Pagos)
- **Registro financiero**: `amount > 0` = pagos, `amount < 0` = devoluciones
- **SSOT financiero**: Separar pagos y devoluciones, nunca mezclar en sum()

### Eventos Livewire y DOM
```php
protected $listeners = [
    'room-created' => '$refresh',
    'room-updated' => '$refresh',
    'refreshRooms' => 'loadRooms',
];
```

### Relación con Blade y Alpine.js
- **Livewire**: Estado y lógica de negocio
- **Blade**: Rendering de vistas y modales
- **Alpine.js**: Interactividad del frontend, reseteo de estado

---

## 3️⃣ Estados del Sistema

### Estados de Habitación (RoomDisplayStatus)

#### free_clean
- **Descripción**: Habitación limpia y disponible
- **Acciones habilitadas**: Quick Rent, Asignar huéspedes
- **Cuándo cambia**: Al marcar como limpia o al crear habitación

#### occupied
- **Descripción**: Habitación actualmente ocupada
- **Acciones habilitadas**: Ver detalle, Continuar estadía (si es checkout hoy)
- **Cuándo cambia**: Al iniciar check-in o continuar estadía

#### pending_checkout
- **Descripción**: Checkout programado para hoy
- **Acciones habilitadas**: Continuar estadía, Liberar habitación
- **Cuándo cambia**: Cuando checkout_date = hoy

#### pending_cleaning
- **Descripción**: Habitación liberada pero sin limpiar
- **Acciones habilitadas**: Marcar como limpia
- **Cuándo cambia**: Al liberar habitación o continuar estadía

#### maintenance
- **Descripción**: Habitación en mantenimiento
- **Acciones habilitadas**: Ver detalles (solo lectura)
- **Cuándo cambia**: Al crear bloqueo de mantenimiento

### Estados de Limpieza

#### limpia
- **Condición**: `last_cleaned_at` no es nulo
- **Acción**: Habitación disponible para ocupación
- **Trigger**: `markRoomAsClean()`

#### pendiente_por_aseo
- **Condición**: `last_cleaned_at` es nulo
- **Acción**: No permite nueva ocupación
- **Trigger**: Liberación o continuación de estadía

---

## 4️⃣ Flujo Diario de Operación Hotelera

### 1. Inicio del Día
```php
public function mount($date = null, $search = null, $status = null)
{
    $this->currentDate = $date ? Carbon::parse($date) : now();
    $this->date = $this->currentDate;
}
```

### 2. Habitaciones Ocupadas
- **Verificación**: `getAvailabilityService()->getStayForDate($date)`
- **Estado**: `active` o `pending_checkout`
- **Cálculo**: Basado en stays reales, no en reservations

### 3. Continuar Estadía
```php
public function continueStay(int $roomId): void
{
    // Extiende checkout por un día
    $newCheckOutDate = $checkoutDate->copy()->addDay();
    
    // Genera noche para la noche real (crítico)
    $nightToCharge = $newCheckOutDate->copy()->subDay();
    $this->ensureNightForDate($stay, $nightToCharge);
    
    // Marca como pendiente por aseo
    $room->update(['last_cleaned_at' => null]);
}
```

### 4. Generación de Noches (stay_nights)
```php
private function ensureNightForDate(\App\Models\Stay $stay, Carbon $date): ?\App\Models\StayNight
{
    // Verificar si ya existe
    $existingNight = \App\Models\StayNight::where('stay_id', $stay->id)
        ->whereDate('date', $date->toDateString())
        ->first();
    
    if ($existingNight) {
        return $existingNight;
    }
    
    // Calcular precio desde tarifas
    $price = $this->findRateForGuests($room, $totalGuests);
    
    // Crear noche
    return \App\Models\StayNight::create([
        'stay_id' => $stay->id,
        'reservation_id' => $reservation->id,
        'room_id' => $room->id,
        'date' => $date->toDateString(),
        'price' => $price,
        'is_paid' => false,
    ]);
}
```

### 5. Pagos por Noche
- **Registro**: `registerPayment()` crea pago en tabla payments
- **Validación**: No exceder saldo pendiente
- **Métodos**: Efectivo, Transferencia

### 6. Checkout
- **Estado**: `pending_checkout` cuando checkout_date = hoy
- **Acciones**: Continuar estadía o Liberar habitación

### 7. Liberación
```php
public function releaseRoom($roomId, $status = null, $paymentMethod = null, $bankName = null, $reference = null)
{
    // 1. Validar deuda y pagar si es necesario
    if ($realDebt > 0) {
        // Pagar todo lo pendiente
        Payment::create(['amount' => $realDebt, ...]);
    }
    
    // 2. Validar balance = 0
    if (abs($finalBalance) > 0.01) {
        // No liberar con saldo pendiente
        return;
    }
    
    // 3. Marcar noches como pagadas
    \App\Models\StayNight::where('reservation_id', $reservation->id)
        ->where('date', '<=', now()->toDateString())
        ->update(['is_paid' => true]);
    
    // 4. Cerrar stay
    $activeStay->update([
        'check_out_at' => now(),
        'status' => 'finished',
    ]);
    
    // 5. Crear historial
    RoomReleaseHistory::create([...]);
}
```

### 8. Limpieza
- **Estado**: `pending_cleaning` después de liberar
- **Acción**: `markRoomAsClean()` actualiza `last_cleaned_at`
- **Resultado**: Habitación vuelve a `free_clean`

### 9. Habitación Lista Nuevamente
- **Disponibilidad**: `free_clean` permite nueva ocupación
- **Ciclo**: Listo para nuevo check-in

---

## 5️⃣ Gestión de Estadías (Stays)

### ¿Qué es una Stay?
Una Stay representa la **ocupación real** de una habitación, con timestamps precisos:
- **check_in_at**: Momento exacto cuando el huésped ocupa la habitación
- **check_out_at**: Momento exacto cuando el huésped deja la habitación
- **status**: `active`, `pending_checkout`, `finished`

### ¿Cómo se crea?
```php
// Se crea automáticamente al hacer check-in
// El sistema crea una Stay cuando:
// 1. Hay una ReservationRoom válida
// 2. La fecha actual intersecta con el rango de fechas
// 3. La habitación está disponible
```

### ¿Cuándo se considera activa?
```php
public function occupiesDate($date = null): bool
{
    $date = $date ?? now()->startOfDay();
    $startOfDay = $date->copy()->startOfDay();
    $endOfDay = $date->copy()->endOfDay();

    $hasStartedBeforeEndOfDay = $this->check_in_at?->lt($endOfDay) ?? false;
    $hasNotEndedBeforeStartOfDay = $this->check_out_at === null || $this->check_out_at->gt($startOfDay);

    return $hasStartedBeforeEndOfDay && $hasNotEndedBeforeStartOfDay;
}
```

### Relación con Reservation y Room
```
Reservation (planificación)
 └── ReservationRoom (fechas)
       └── Stay (ocupación real)
            └── Room (física)
```

### Diferencia entre extender estadía y crear nueva
- **Extender estadía**: `continueStay()` modifica checkout_date, misma Stay
- **Crear nueva**: Se crea nueva Stay cuando hay nueva ocupación

---

## 6️⃣ Sistema de Cobro por Noches (stay_nights)

### ¿Por qué se creó stay_nights?
1. **Precisión individual**: Cada noche tiene su propio registro
2. **Estado de pago**: Rastrear qué noches están pagadas vs pendientes
3. **Precios variables**: Diferentes tarifas según cantidad de huéspedes
4. **Auditoría**: Historial completo de cobros por noche
5. **SSOT financiero**: Fuente única de verdad para totales

### Estructura de la Tabla
```sql
stay_nights:
- stay_id (FK)
- reservation_id (FK)
- room_id (FK)
- date (DATE)
- price (DECIMAL)
- is_paid (BOOLEAN)
```

### Reglas Fundamentales

#### Una noche por fecha
```php
// Verificación en ensureNightForDate()
$existingNight = \App\Models\StayNight::where('stay_id', $stay->id)
    ->whereDate('date', $date->toDateString())
    ->first();

if ($existingNight) {
    return $existingNight; // Ya existe, no duplicar
}
```

#### No incluir checkout
```php
// 🔐 REGLA HOTELERA: La noche del check-out NO se cobra
// Ejemplo: Check-in 18, Check-out 20 → Noches: 18 y 19 (NO 20)
while ($currentDate->lt($checkOut)) {
    $this->ensureNightForDate($stay, $currentDate);
    $currentDate->addDay();
}
```

#### No generar noches futuras
```php
// 🔐 PROTECCIÓN: Solo generar noches para HOY
if ($this->date->isAfter($today)) {
    return; // Fecha futura: NO generar noches
}
```

### Método ensureNightForDate()
Es el método crítico que garantiza la existencia de noches:

1. **Verificar existencia**: Si ya existe, retornar
2. **Calcular precio**: Desde tarifas de habitación
3. **Crear registro**: Con precio y estado pendiente
4. **Logging**: Registro completo para auditoría

### Integración con otros métodos

#### openRoomDetail()
```php
// Generar noches faltantes para todo el rango
$currentDate = $checkIn->copy();
while ($currentDate->lt($checkOut)) {
    $this->ensureNightForDate($stay, $currentDate);
    $currentDate->addDay();
}
```

#### continueStay()
```php
// Generar noche para la noche real (crítico)
$nightToCharge = $newCheckOutDate->copy()->subDay();
$this->ensureNightForDate($stay, $nightToCharge);
```

#### nextDay()
```php
// Generar noche para hoy si hay stay activa
foreach ($activeStays as $stay) {
    $this->ensureNightForDate($stay, $today);
}
```

#### releaseRoom()
```php
// Marcar todas las noches hasta hoy como pagadas
\App\Models\StayNight::where('reservation_id', $reservation->id)
    ->where('date', '<=', now()->toDateString())
    ->update(['is_paid' => true]);
```

---

## 7️⃣ Cálculo Financiero (SSOT)

### ¿Por qué reservations.total_amount es fallback?
- ** reservations.total_amount**: Estimación inicial, puede cambiar
- **stay_nights**: Registro real de cada noche con precio exacto
- **SSOT**: Single Source of Truth = stay_nights.sum('price')

### ¿Cómo se calcula?

#### Total Hospedaje
```php
// ✅ NUEVO SSOT: Calcular desde stay_nights
$totalHospedaje = (float)\App\Models\StayNight::where('reservation_id', $reservation->id)
    ->sum('price');

// FALLBACK: Si no hay noches aún
if ($totalHospedaje <= 0) {
    $totalHospedaje = (float)($reservation->total_amount ?? 0);
}
```

#### Pagos y Devoluciones
```php
// SOLO pagos positivos (dinero recibido)
$abonoRealizado = (float)($payments->where('amount', '>', 0)->sum('amount') ?? 0);

// SOLO devoluciones (dinero devuelto, valores negativos)
$refundsTotal = abs((float)($payments->where('amount', '<', 0)->sum('amount') ?? 0));
```

#### Saldo Pendiente
```php
// Fórmula correcta con pagos y devoluciones separados
$totalDebt = ($totalHospedaje - $abonoRealizado) + $refundsTotal + $salesDebt;
```

### Manejo de pagos positivos vs negativos
- **amount > 0**: Dinero recibido del cliente
- **amount < 0**: Devoluciones al cliente
- **Nunca mezclar**: `sum(amount)` cancelaría pagos y devoluciones

### Casos de saldo a favor REAL vs semántico
- **REAL**: Cliente pagó más de lo debido (devolución pendiente)
- **Semántico**: Abono que parece saldo a favor pero es pago parcial

---

## 8️⃣ Pagos y Abonos

### registerPayment()
```php
public function registerPayment($reservationId, $amount, $paymentMethod, $bankName = null, $reference = null, $notes = null, $nightDate = null)
{
    // 1. Validaciones básicas
    $reservation = Reservation::find($reservationId);
    $amount = (float)$amount;
    
    // 2. Validar saldo pendiente
    if ($amount > $balanceDueBefore) {
        // No permitir pagar más de lo debido
        return false;
    }
    
    // 3. Crear pago
    $payment = Payment::create([
        'reservation_id' => $reservation->id,
        'amount' => $amount,
        'payment_method_id' => $paymentMethodId,
        'bank_name' => $bankName,
        'reference' => $reference,
        'paid_at' => now(),
        'created_by' => auth()->id(),
    ]);
    
    // 4. Si es pago por noche específica
    if ($nightDate) {
        // Marcar noche específica como pagada
        \App\Models\StayNight::where('reservation_id', $reservation->id)
            ->whereDate('date', $nightDate)
            ->update(['is_paid' => true]);
    }
}
```

### Modal payment-modal
- **Componente**: Interfaz para registrar pagos
- **Validación**: No permite exceder saldo pendiente
- **Métodos**: Efectivo, Transferencia

### Pago por noche específica
```php
// Al pagar una noche específica
if ($nightDate) {
    \App\Models\StayNight::where('reservation_id', $reservation->id)
        ->whereDate('date', $nightDate)
        ->update(['is_paid' => true]);
}
```

### Por qué pagar una noche marca stay_night como pagada
- **Precisión**: Rastreo exacto de qué noches están pagadas
- **Auditoría**: Historial completo por noche
- **SSOT**: stay_nights es fuente de verdad financiera

### Qué NO hace el pago
- **NO libera habitación**: Liberación requiere proceso completo
- **NO modifica estado operativo**: Solo afecta estado financiero
- **NO genera noches futuras**: Protección temporal

---

## 9️⃣ Liberación de Habitación (releaseRoom)

### ¿Qué valida?
```php
// 1. No liberar fechas históricas
if ($availabilityService->isHistoricDate($today)) {
    return;
}

// 2. Debe haber stay activa
$activeStay = $availabilityService->getStayForDate($today);
if (!$activeStay) {
    return;
}

// 3. Balance debe ser 0
if (abs($finalBalance) > 0.01) {
    return; // No liberar con saldo pendiente
}
```

### ¿Qué estados cambia?
```php
// 1. Cerrar stay
$activeStay->update([
    'check_out_at' => now(),
    'status' => 'finished',
]);

// 2. Actualizar reserva
$reservation->balance_due = 0;
$reservation->payment_status_id = $paidStatusId;

// 3. Marcar habitación como pendiente limpieza
// (implícito por last_cleaned_at = null)
```

### ¿Qué noches marca como pagadas?
```php
// 🔥 CRÍTICO: Solo noches hasta hoy
\App\Models\StayNight::where('reservation_id', $reservation->id)
    ->where('date', '<=', now()->toDateString()) // Protección
    ->where('is_paid', false)
    ->update(['is_paid' => true]);
```

### ¿Qué información guarda en historial?
```php
RoomReleaseHistory::create([
    'room_id' => $room->id,
    'customer_id' => $reservation->customer_id,
    'customer_name' => $reservation->customer->name,
    'customer_identification' => $reservation->customer->taxProfile->identification,
    'release_date' => now()->toDateString(),
    'released_by' => auth()->id(),
    'total_amount' => $totalAmount,
    'total_paid' => $totalPaid,
    'total_refunded' => $totalRefunds,
    'sales_total' => $salesTotal,
    'notes' => 'Liberación automática',
]);
```

### Protección contra pagar noches futuras
```php
// 🔐 PROTECCIÓN: Solo marcar noches hasta hoy
->where('date', '<=', now()->toDateString())
```

---

## 🔟 Historial y Consultas

### openRoomDetail()
```php
public function openRoomDetail($roomId)
{
    // 1. Cargar habitación con relaciones
    $room = Room::with([
        'reservationRooms.reservation.customer',
        'reservationRooms.reservation.sales',
        'reservationRooms.reservation.payments',
        'rates',
    ])->find($roomId);
    
    // 2. Generar noches faltantes
    $currentDate = $checkIn->copy();
    while ($currentDate->lt($checkOut)) {
        $this->ensureNightForDate($stay, $currentDate);
        $currentDate->addDay();
    }
    
    // 3. Calcular totales desde SSOT
    $totalHospedaje = \App\Models\StayNight::where('reservation_id', $reservation->id)
        ->sum('price');
    
    // 4. Preparar datos para vista
    $this->detailData = [
        'room' => $room,
        'reservation' => $activeReservation,
        'stay_history' => $stayHistory,
        'total_hospedaje' => $totalHospedaje,
        'total_debt' => $totalDebt,
    ];
}
```

### Historial diario de una habitación
```php
// stay_history desde stay_nights
$stayHistory = $stayNights->map(function($night) {
    return [
        'date' => $night->date->format('Y-m-d'),
        'price' => (float)$night->price,
        'is_paid' => (bool)$night->is_paid,
    ];
})->toArray();
```

### Soporte para liberaciones sin huésped asignado
```php
// Guard clause para habitaciones sin stay
if (!$stay || !$stay->reservation) {
    return [
        'room_number' => $room->room_number,
        'guests' => [],
        'main_guest' => null,
    ];
}
```

### Diferencia entre historial operativo vs financiero
- **Operativo**: Estados de habitación, limpieza, mantenimiento
- **Financiero**: Pagos, noches cobradas, saldo pendiente

---

## 1️⃣1️⃣ Eventos y Comunicación

### Eventos Livewire usados
```php
protected $listeners = [
    'room-created' => '$refresh',
    'room-updated' => '$refresh',
    'refreshRooms' => 'loadRooms',
    'register-payment' => 'handleRegisterPayment',
];
```

### Eventos DOM personalizados
```php
// Control de estado
$this->dispatch('room-view-changed', date: $this->date->toDateString());

// Liberación de habitación
$this->dispatch('room-release-start', roomId: $roomId);
$this->dispatch('room-release-finished', roomId: $roomId);

// Notificaciones
$this->dispatch('notify', [
    'type' => 'success',
    'message' => 'Operación completada'
]);

// Limpieza
$this->dispatch('room-marked-clean', roomId: $room->id);
```

### ¿Qué refresca cada evento?
- **'$refresh'**: Recarga completa del componente
- **'loadRooms'**: Recarga consulta de habitaciones
- **'room-view-changed'**: Resetea estado de Alpine.js

### ¿Qué modales dependen de ellos?
- **payment-modal**: Depende de 'register-payment'
- **room-detail-modal**: Depende de '$refresh'
- **release-confirmation**: Depende de 'room-release-start/finished'

---

## 1️⃣2️⃣ Errores Comunes y Casos Especiales

### Noche pagada vs pendiente
```php
// ERROR: Confundir estado de pago con estado de habitación
// CORRECTO: Una habitación puede estar ocupada con noches pagadas

// Verificación correcta
$night = \App\Models\StayNight::where('reservation_id', $reservation->id)
    ->whereDate('date', $date)
    ->first();

$isNightPaid = $night?->is_paid ?? false;
```

### Abonos que parecen saldo a favor
```php
// ERROR: Usar sum(amount) que cancela pagos y devoluciones
$wrongTotal = $payments->sum('amount'); // ❌ Incorrecto

// CORRECTO: Separar pagos y devoluciones
$payments = (float)($payments->where('amount', '>', 0)->sum('amount') ?? 0);
$refunds = abs((float)($payments->where('amount', '<', 0)->sum('amount') ?? 0));
```

### Habitación sin huésped
```php
// Protección en loadRoomGuests()
if (!$stay || !$stay->reservation) {
    return [
        'room_number' => $room->room_number,
        'guests' => [],
        'main_guest' => null,
    ];
}
```

### Múltiples ocupaciones en un día
```php
// El sistema soporta múltiples stays en una habitación
// Cada stay tiene su propio rango de fechas
// getStayForDate() retorna la stay activa para la fecha específica
```

### Continuar estadía sin limpiar
```php
// 🔐 REGLA HOTELERA: Continuar estadía = habitación queda pendiente por aseo
$room->update(['last_cleaned_at' => null]);
```

---

## 1️⃣3️⃣ Decisiones de Diseño (IMPORTANTE)

### ¿Por qué stay_nights?
1. **Precisión**: Cada noche tiene su propio registro
2. **Auditoría**: Rastreo individual de pagos por noche
3. **Flexibilidad**: Precios variables según huéspedes
4. **SSOT**: Fuente única de verdad financiera

### ¿Por qué excluir checkout?
```php
// 🔐 REGLA HOTELERA: La noche del checkout NO se cobra
// Ejemplo: Check-in viernes 18, Checkout domingo 20
// Noches cobradas: Viernes 18, Sábado 19
// Noche domingo 20: NO se cobra (el huésped se va)
while ($currentDate->lt($checkOut)) {
    $this->ensureNightForDate($stay, $currentDate);
    $currentDate->addDay();
}
```

### ¿Por qué no generar noches futuras?
```php
// 🔐 PROTECCIÓN: Solo generar noches para HOY
if ($this->date->isAfter($today)) {
    return; // Fecha futura: NO generar noches
}
```

### ¿Por qué no limpiar automáticamente al continuar estadía?
```php
// 🔐 REGLA HOTELERA: Toda extensión de estadía ensucia la habitación
// Aunque el huésped continúe, el personal debe inspeccionar y limpiar
$room->update(['last_cleaned_at' => null]);
```

### ¿Por qué separación entre reserva y estadía?
- **Reserva**: Planificación futura, fechas, cliente
- **Estadía**: Ocupación real, timestamps, estado operativo
- **Claridad**: Distinción clara entre intención y realidad

---

## 1️⃣4️⃣ Buenas Prácticas y Reglas de Oro

### Qué nunca romper
1. **NO generar noches para fechas futuras**
2. **NO liberar habitación con saldo pendiente**
3. **NO mezclar pagos y devoluciones en sum()**
4. **NO usar reservations.total_amount como SSOT financiero**
5. **NO permitir cambios en fechas históricas**

### Qué siempre validar
1. **Balance = 0 antes de liberar**
2. **Fecha actual vs checkout_date**
3. **Métodos de pago válidos**
4. **Usuario autenticado en pagos**
5. **Existencia de stay activa**

### Qué usar como SSOT
1. **Financiero**: stay_nights.sum('price')
2. **Operativo**: stays con timestamps
3. **Pagos**: tabla payments (separado por signo)
4. **Huéspedes**: reservations.client_id + reservationRoom.getGuests()

### Qué no recalcular manualmente
1. **Total de hospedaje**: Usar stay_nights
2. **Saldo pendiente**: Calcular desde pagos reales
3. **Estado de habitación**: Usar servicios de disponibilidad
4. **Fechas de checkout**: Usar reservation_rooms.check_out_date

---

## 1️⃣5️⃣ Diagrama Conceptual (texto)

```
RESERVATION (Planificación)
 ├── client_id → Customer (huésped principal)
 ├── total_amount (estimación inicial)
 └── ReservationRoom (fechas por habitación)
       ├── room_id → Room
       ├── check_in_date (DATE)
       ├── check_out_date (DATE)
       └── getGuests() → Customer[] (adicionales)
             └── reservation_room_guests → reservation_guests → customers

STAY (Ocupación Real)
 ├── reservation_id → Reservation
 ├── room_id → Room
 ├── check_in_at (TIMESTAMP)
 ├── check_out_at (TIMESTAMP, nullable)
 └── status (active/pending_checkout/finished)

STAY_NIGHT (Noche Cobrable) ← SSOT FINANCIERO
 ├── stay_id → Stay
 ├── reservation_id → Reservation
 ├── room_id → Room
 ├── date (DATE, una noche por fecha)
 ├── price (DECIMAL, desde tarifas)
 └── is_paid (BOOLEAN)

PAYMENT (Registro Financiero)
 ├── reservation_id → Reservation
 ├── amount (DECIMAL, >0 pagos, <0 devoluciones)
 ├── payment_method_id → PaymentMethod
 ├── created_by → User
 └── paid_at (TIMESTAMP)

ROOM (Habitación Física)
 ├── room_number
 ├── last_cleaned_at (nullable = pendiente limpieza)
 ├── rates → RoomRate (tarifas por huéspedes)
 └── getAvailabilityService()
       ├── getStayForDate() → Stay
       ├── isHistoricDate() → Boolean
       └── getDisplayStatus() → RoomDisplayStatus

ROOM_RELEASE_HISTORY (Historial de Liberación)
 ├── room_id → Room
 ├── customer_id → Customer
 ├── release_date (DATE)
 ├── released_by → User
 └── totales financieros (SSOT)
```

---

## 📝 Conclusión

RoomManager es el corazón del sistema hotelero que implementa la lógica de negocio real de un hotel:

1. **Separación clara**: Reservas (planificación) vs Estadías (ocupación real)
2. **SSOT financiero**: stay_nights como fuente única de verdad
3. **Reglas hoteleras**: Checkout no se cobra, noches futuras protegidas
4. **Integridad**: Validaciones estrictas antes de liberar
5. **Auditoría**: Historial completo de operaciones

El módulo está diseñado para ser robusto, predecible y seguir las mejores prácticas de la industria hotelera real.
