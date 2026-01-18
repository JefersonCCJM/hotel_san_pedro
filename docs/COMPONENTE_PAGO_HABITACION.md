# Componente de Información de Pago de Habitación - Análisis Técnico Completo

## 📋 Índice

1. [Visión General](#visión-general)
2. [Estructura de Archivos](#estructura-de-archivos)
3. [Flujo de Datos](#flujo-de-datos)
4. [Cálculo Financiero](#cálculo-financiero)
5. [Estados Visuales](#estados-visuales)
6. [Integración con Room Row](#integración-con-room-row)
7. [Problema Identificado (SSOT Inconsistente)](#problema-identificado-ssot-inconsistente)
8. [Single Source of Truth (SSOT)](#single-source-of-truth-ssot)

---

## 🎯 Visión General

El componente **`room-payment-info`** (`resources/views/components/room-manager/room-payment-info.blade.php`) muestra información financiera resumida de una habitación ocupada en la vista de tabla del Room Manager.

### Propósito Principal

- **Mostrar estado de pago** (Al día, Parcial, Pendiente)
- **Mostrar saldo total pendiente** (si aplica)
- **Mostrar monto abonado** (si hay abono parcial)
- **Mostrar estado de noche pagada** (badge "NOCHE PAGA" / "NOCHE PENDIENTE")
- **Indicar inconsistencias de datos** (stay sin reserva)

### Características Clave

- ✅ **Guard Clause**: Si no hay `$stay`, muestra "Cuenta cerrada" y termina
- ✅ **SSOT desde Stay**: Obtiene `$reservation` desde `$stay->reservation`
- ✅ **Eager Loading**: Usa `loadMissing()` para optimizar queries
- ⚠️ **Problema SSOT**: `paymentsTotal` mezcla pagos y devoluciones (necesita corrección)

---

## 📁 Estructura de Archivos

```
resources/views/components/room-manager/
└── room-payment-info.blade.php          # Componente de información de pago

resources/views/components/room-manager/
└── room-row.blade.php                   # Componente padre que usa room-payment-info

app/Livewire/
└── RoomManager.php                      # Controlador Livewire (carga habitaciones y stays)
```

### Ubicación del Componente

**Archivo:**
- `resources/views/components/room-manager/room-payment-info.blade.php`

**Inclusión en room-row:**
```blade
{{-- resources/views/components/room-manager/room-row.blade.php (línea ~202) --}}
<x-room-manager.room-payment-info :room="$room" :stay="$stay" />
```

---

## 🔄 Flujo de Datos

### 1. Carga Inicial

```
RoomManager::loadRooms() se ejecuta
    ↓
Se obtienen habitaciones con relaciones cargadas
    ↓
Para cada habitación ocupada:
    Se obtiene stay activa: $stay = getStayForDate($room, $date)
    Se calcula is_night_paid (en loadRooms)
    Se calcula total_debt (en loadRooms)
    ↓
room-row.blade.php recibe $room y $stay
    ↓
room-payment-info recibe :room="$room" :stay="$stay"
    ↓
Componente calcula valores financieros desde $reservation
    ↓
Renderiza UI según estado financiero
```

### 2. Props Recibidas

```php
@props(['room', 'stay'])

// $room: Modelo Room con propiedades calculadas:
//   - $room->is_night_paid (bool) - Calculado en loadRooms()
//   - $room->total_debt (float) - Calculado en loadRooms()

// $stay: Modelo Stay (o null) - Obtenido desde RoomManager::getStayForDate()
```

### 3. Obtención de Reserva

```php
// Guard Clause: Si no hay stay, no hay información financiera
if (!$stay) {
    echo '<span class="text-xs text-gray-400 italic">Cuenta cerrada</span>';
    return;
}

// SSOT: Reserva se obtiene desde la stay
$reservation = $stay->reservation;
```

---

## 💰 Cálculo Financiero

### 1. Cálculo de `paymentsTotal` (PROBLEMA IDENTIFICADO)

**Código actual (línea ~24):**
```php
$paymentsTotal = (float)($reservation->payments?->sum('amount') ?? 0);
```

**Problema:**
- ❌ **Mezcla pagos positivos y devoluciones negativas** en un solo `sum()`
- ❌ **Ejemplo**: Pago +80.000, Devolución -20.000 → `paymentsTotal = 60.000` (INCORRECTO)
- ❌ **Correcto debería ser**: `abonoRealizado = 80.000`, `refundsTotal = 20.000`

**Corrección necesaria:**
```php
// Separar pagos y devoluciones (SSOT financiero)
$abonoRealizado = (float)($reservation->payments->where('amount', '>', 0)->sum('amount') ?? 0);
$refundsTotal = abs((float)($reservation->payments->where('amount', '<', 0)->sum('amount') ?? 0));

// Usar abonoRealizado para cálculos
$paymentsTotal = $abonoRealizado; // Para mantener compatibilidad
```

### 2. Cálculo de `totalAmount`

**Código actual (línea ~25):**
```php
$totalAmount = (float)($reservation->total_amount ?? 0);
```

✅ **Correcto**: Usa `reservation.total_amount` como SSOT del hospedaje.

### 3. Cálculo de `salesDebt`

**Código actual (línea ~26):**
```php
$salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
```

✅ **Correcto**: Solo cuenta consumos no pagados.

### 4. Cálculo de `balanceDue`

**Código actual (línea ~28-33):**
```php
// Preferir balance_due almacenado (source of truth) si existe
if ($reservation->balance_due !== null) {
    $balanceDue = (float)$reservation->balance_due + $salesDebt;
} else {
    $balanceDue = ($totalAmount - $paymentsTotal) + $salesDebt;
}
```

⚠️ **Problema potencial**:
- Si `paymentsTotal` mezcla pagos y devoluciones, `balanceDue` también estará incorrecto.
- La fórmula `($totalAmount - $paymentsTotal) + $salesDebt` es correcta **SOLO si** `paymentsTotal` solo incluye pagos positivos.

**Fórmula correcta con devoluciones separadas:**
```php
$balanceDue = ($totalAmount - $abonoRealizado) + $refundsTotal + $salesDebt;
```

---

## 🎨 Estados Visuales

### 1. Estado de Noche Pagada (Badge)

**Condición:** `isset($room->is_night_paid)`

**Estados:**
- **NOCHE PAGA** (verde): `$room->is_night_paid === true`
- **NOCHE PENDIENTE** (rojo): `$room->is_night_paid === false`

**Ubicación en código:** Línea ~43-53

**Nota:** `is_night_paid` se calcula en `RoomManager::loadRooms()` basándose en:
- `pricePerNight * nightsConsumed` vs `paymentsTotal`
- Si `paymentsTotal >= expectedPaidUntilToday` → `is_night_paid = true`

### 2. Estado Financiero

#### A. Pago Parcial (`balanceDue > 0 && $paid > 0`)

**Visualización:**
- Badge: "Parcial" (amarillo)
- Saldo Total: `$balanceDue` (amarillo)
- Abonado: `$paid` (gris)

**Ubicación en código:** Línea ~56-65

#### B. Pendiente de Pago (`balanceDue > 0 && $paid == 0`)

**Visualización:**
- Badge: "Pendiente" (rojo)
- Saldo Total: `$balanceDue` (rojo)

**Ubicación en código:** Línea ~66-74

#### C. Al Día (`balanceDue <= 0`)

**Visualización:**
- Badge: "Al día" (verde)

**Ubicación en código:** Línea ~75-79

### 3. Caso Edge: Stay sin Reserva

**Condición:** `!$reservation` (pero `$stay` existe)

**Visualización:**
- Badge: "Sin cuenta asociada" (amarillo)
- Mensaje: "No hay reserva ligada a esta estadía."
- Botón: "Ver detalles" (llama `openRoomDetail($room->id)`)

**Ubicación en código:** Línea ~82-96

---

## 🔗 Integración con Room Row

### Ubicación en room-row.blade.php

```blade
{{-- Línea ~199-203 --}}
<div x-show="shouldShowGuestInfo">
    {{-- SINGLE SOURCE OF TRUTH: Pasar $stay explícitamente al componente --}}
    <x-room-manager.room-payment-info :room="$room" :stay="$stay" />
</div>
```

### Condición de Visibilidad

El componente solo se muestra cuando `shouldShowGuestInfo === true` (definido en Alpine.js de `room-row`):

```javascript
get shouldShowGuestInfo() {
    return !this.isReleasing 
        && !this.recentlyReleased 
        && ['occupied', 'pending_checkout'].includes(this.operationalStatus);
}
```

**Regla:** Solo se muestra información de pago cuando:
- ✅ La habitación está ocupada o pendiente de checkout
- ✅ NO se está liberando (`!isReleasing`)
- ✅ NO se liberó recientemente (`!recentlyReleased`)

---

## ⚠️ Problema Identificado (SSOT Inconsistente)

### Problema Principal

**Línea ~24 del componente:**
```php
$paymentsTotal = (float)($reservation->payments?->sum('amount') ?? 0);
```

**Este cálculo mezcla pagos positivos y devoluciones negativas**, causando:

1. ❌ **Abono incorrecto**: Si hay devoluciones, el abono mostrado está reducido incorrectamente
2. ❌ **Deuda incorrecta**: `balanceDue` se calcula mal si hay devoluciones
3. ❌ **Inconsistencia con Room Detail**: Room Detail separa pagos y devoluciones correctamente

### Ejemplo del Problema

**Escenario:**
- Total hospedaje: 80.000
- Pago recibido: +80.000
- Devolución registrada: -20.000

**Cálculo actual (INCORRECTO):**
```php
$paymentsTotal = sum([80000, -20000]) = 60000; // ❌ INCORRECTO
$balanceDue = (80000 - 60000) + 0 = 20000;     // ❌ INCORRECTO (debería ser 0)
```

**Cálculo correcto:**
```php
$abonoRealizado = sum([80000]) = 80000;        // ✅ CORRECTO
$refundsTotal = abs(sum([-20000])) = 20000;    // ✅ CORRECTO
$balanceDue = (80000 - 80000) + 20000 + 0 = 20000; // ✅ CORRECTO (se le debe al cliente)
```

### Corrección Necesaria

```php
// Separar pagos y devoluciones (SSOT financiero)
$abonoRealizado = (float)($reservation->payments->where('amount', '>', 0)->sum('amount') ?? 0);
$refundsTotal = abs((float)($reservation->payments->where('amount', '<', 0)->sum('amount') ?? 0));

// Para compatibilidad, mantener $paymentsTotal como pagos reales
$paymentsTotal = $abonoRealizado;

// Calcular balanceDue correctamente con devoluciones
if ($reservation->balance_due !== null) {
    $balanceDue = (float)$reservation->balance_due + $salesDebt;
} else {
    $balanceDue = ($totalAmount - $abonoRealizado) + $refundsTotal + $salesDebt;
}

$paid = $abonoRealizado; // Usar abono real, no mezclado
```

---

## ✅ Single Source of Truth (SSOT)

### Fuentes de Verdad Actuales (Correctas)

1. **Stay activa**: `$stay` es SSOT para determinar si hay ocupación
   - Se obtiene desde `RoomManager::getStayForDate($room, $date)`

2. **Reserva**: `$reservation = $stay->reservation`
   - SSOT para información de la reserva

3. **Total del hospedaje**: `$reservation->total_amount`
   - SSOT absoluto (se define al arrendar, no se recalcula)

4. **Consumos pendientes**: `$reservation->sales->where('is_paid', false)->sum('total')`
   - SSOT para consumos no pagados

### Fuentes de Verdad que Necesitan Corrección

1. **Abono realizado**: Actualmente usa `sum('amount')` que mezcla pagos y devoluciones
   - **Debería usar**: `$payments->where('amount', '>', 0)->sum('amount')`

2. **Balance due calculado**: Depende de `paymentsTotal` incorrecto
   - **Debería incluir**: `($totalAmount - $abonoRealizado) + $refundsTotal + $salesDebt`

### Fuentes Derivadas (No SSOT)

1. **`$room->is_night_paid`**: Se calcula en `loadRooms()`, no se persiste
2. **`$room->total_debt`**: Se calcula on-the-fly, pero puede usar `reservation.balance_due` almacenado

---

## 🔄 Flujo de Actualización

### Después de Registrar un Pago

```
Usuario registra pago desde modal de pago
    ↓
RoomManager::registerPayment() guarda en payments
    ↓
RoomManager::loadRooms() recalcula is_night_paid y total_debt
    ↓
room-row se re-renderiza con nuevos valores
    ↓
room-payment-info recalcula paymentsTotal y balanceDue
    ↓
UI se actualiza automáticamente (Livewire)
```

### Después de Registrar una Devolución

**Problema actual**: Como `paymentsTotal` mezcla pagos y devoluciones, el componente puede mostrar valores incorrectos.

**Con corrección**: El componente mostraría:
- Abono: solo pagos positivos (correcto)
- Deuda: incluiría devoluciones correctamente

---

## 📝 Relación con Otros Componentes

### 1. `room-guest-info`

- **Ubicación**: Misma fila de `room-row`, columna de huésped
- **Relación**: Ambos usan `$stay` como SSOT
- **Condición de visibilidad**: Misma (`shouldShowGuestInfo`)

### 2. `room-detail-modal`

- **Relación**: Muestra información financiera detallada
- **Diferencias**:
  - Room Detail separa pagos y devoluciones correctamente (desde corrección reciente)
  - Room Payment Info necesita la misma corrección

### 3. `room-release-confirmation-modal`

- **Relación**: Evalúa pagos antes de liberar habitación
- **SSOT compartido**: `payments` table

---

## 🧠 Resumen Ejecutivo

El componente **`room-payment-info`** es un widget visual que muestra el estado financiero resumido de una habitación ocupada. Usa `$stay` como SSOT para obtener la reserva y calcula valores financieros desde `$reservation`.

**Problema crítico identificado:**
- ❌ `paymentsTotal` mezcla pagos positivos y devoluciones negativas
- ❌ Esto causa cálculos incorrectos de `balanceDue`
- ❌ Inconsistencia con `room-detail-modal` (que ya separa correctamente)

**Corrección necesaria:**
- ✅ Separar `abonoRealizado` (solo `amount > 0`) de `refundsTotal` (solo `amount < 0`)
- ✅ Usar `abonoRealizado` para `balanceDue` y mostrar en UI
- ✅ Incluir `refundsTotal` en cálculo de `balanceDue` si es necesario

**Arquitectura:**
- ✅ SSOT para stay/reservation está bien definido
- ✅ Guard clauses protegen contra casos edge
- ✅ Eager loading optimiza queries
- ⚠️ Cálculo financiero necesita alinearse con correcciones recientes en `openRoomDetail()`
