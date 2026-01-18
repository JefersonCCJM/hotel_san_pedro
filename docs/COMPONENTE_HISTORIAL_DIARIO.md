# 🧩 Contexto Completo: `room-daily-history-modal.blade.php`

## 📍 Ubicación
`resources/views/components/room-manager/room-daily-history-modal.blade.php`

---

## 🎯 Propósito

Este componente muestra el **historial diario de liberaciones** de una habitación en un día específico (por defecto HOY). 

**Características clave:**
- ✅ Consulta histórica (NO operativa)
- ✅ Usa `room_release_history` (tabla de auditoría inmutable)
- ✅ Muestra TODAS las liberaciones que ocurrieron en el día
- ✅ Indica estado de pago de cada liberación
- ✅ Timeline visual con detalles financieros

---

## 🔄 Diferencias con otros modales

| Aspecto | `room-detail-modal` | `room-daily-history-modal` |
|---------|-------------------|---------------------------|
| **Fuente** | `stays` + `reservations` activas | `room_release_history` |
| **Propósito** | Estado operativo actual | Historial cerrado (auditoría) |
| **Mutabilidad** | Mutable (puede modificarse) | Inmutable (snapshot) |
| **Cantidad** | 1 reserva activa | N liberaciones del día |
| **Uso** | Operación (pagos, ventas) | Consulta (auditoría, reportes) |

---

## 🛠️ Tecnología

- **Alpine.js** con `@entangle` para sincronización Livewire
- **Livewire** para carga de datos
- **No usa eventos personalizados** (diferente a `room-release-confirmation-modal`)

---

## 📐 Estructura del Componente

### **Props recibidas:**
```php
@props(['roomDailyHistoryData'])
```

### **Estado Alpine.js:**
```blade
x-show="roomDailyHistoryModal"
```
- Sincronizado con Livewire via `@entangle('roomDailyHistoryModal')`
- Definido en `room-manager.blade.php` en el `x-data` principal

---

## 🚀 Cómo se Dispara

### **Paso 1: Usuario hace click en botón "Historial del día"**

**Desde `room-actions-menu.blade.php` (línea 118):**
```blade
<button type="button"
    wire:click="openRoomDailyHistory({{ $room->id }})"
    wire:loading.attr="disabled"
    title="Historial del día"
    class="...">
    <i class="fas fa-history text-sm"></i>
</button>
```

### **Paso 2: Método Livewire `openRoomDailyHistory()`**

**En `RoomManager.php` (línea ~2196):**

```php
public function openRoomDailyHistory(int $roomId): void
{
    $room = Room::findOrFail($roomId);
    $date = $this->date->toDateString(); // Fecha seleccionada (HOY por defecto)

    // Obtener TODAS las liberaciones de esta habitación en el día seleccionado
    $releases = RoomReleaseHistory::where('room_id', $roomId)
        ->whereDate('release_date', $date)
        ->with('releasedBy')
        ->orderBy('created_at', 'asc') // Primera liberación primero
        ->get();

    // Preparar datos para el modal
    $this->roomDailyHistoryData = [
        'room' => [
            'id' => $room->id,
            'room_number' => $room->room_number,
        ],
        'date' => $date,
        'date_formatted' => $this->date->format('d/m/Y'),
        'total_releases' => $releases->count(),
        'releases' => $releases->map(function ($release) {
            // Determinar estado de la cuenta
            $isPaid = (float)$release->pending_amount <= 0.01; // Tolerancia para floats
            $hasConsumptions = (float)$release->consumptions_total > 0;
            
            return [
                'id' => $release->id,
                'released_at' => $release->created_at->format('H:i'),
                'customer_name' => $release->customer_name,
                'customer_identification' => $release->customer_identification ?? 'N/A',
                'guests_count' => $release->guests_count ?? 1,
                'total_amount' => (float)$release->total_amount,
                'deposit' => (float)$release->deposit,
                'consumptions_total' => (float)$release->consumptions_total,
                'pending_amount' => (float)$release->pending_amount,
                'is_paid' => $isPaid,  // ✅ SIEMPRE true después del fix
                'has_consumptions' => $hasConsumptions,
                'released_by' => $release->releasedBy?->name ?? 'Sistema',
                'target_status' => $release->target_status,
                'check_in_date' => $release->check_in_date?->format('d/m/Y'),
                'check_out_date' => $release->check_out_date?->format('d/m/Y'),
                // ... snapshots JSON
            ];
        })->toArray(),
    ];

    $this->roomDailyHistoryModal = true;
}
```

### **Paso 3: Modal se abre**

Alpine.js detecta `roomDailyHistoryModal = true` via `@entangle` y muestra el modal (`x-show="roomDailyHistoryModal"`).

---

## 📦 Estructura de `roomDailyHistoryData`

```php
[
    'room' => [
        'id' => 5,
        'room_number' => '202',
    ],
    'date' => '2026-01-18',
    'date_formatted' => '18/01/2026',
    'total_releases' => 3,
    'releases' => [
        [
            'id' => 123,
            'released_at' => '09:15',
            'customer_name' => 'Juan Pérez',
            'customer_identification' => '1234567890',
            'guests_count' => 2,
            'total_amount' => 60000.0,
            'deposit' => 60000.0,
            'consumptions_total' => 0.0,
            'pending_amount' => 0.0,  // ✅ SIEMPRE 0 después del fix
            'is_paid' => true,         // ✅ SIEMPRE true después del fix
            'has_consumptions' => false,
            'released_by' => 'María (Recepción)',
            'target_status' => 'free_clean',
            'check_in_date' => '17/01/2026',
            'check_out_date' => '18/01/2026',
            // ... snapshots JSON
        ],
        // ... más liberaciones
    ],
]
```

---

## 🧩 Secciones del Modal

### **1. Header**

- **Título:** "Historial del Día"
- **Subtítulo:** "Hab. 202 - 18/01/2026"
- **Icono:** Reloj histórico (fa-history)
- **Botón cerrar (X)**

### **2. Contador de Liberaciones**

```blade
{{ $roomDailyHistoryData['total_releases'] }} liberación/liberaciones
```

### **3. Timeline de Liberaciones**

Cada liberación muestra una **card** con:

#### **3.1 Header de la Card**
- **Hora:** `released_at` (formato "H:i", ej: "09:15")
- **Badge de Estado:**
  - ✅ **Pagado** (verde): Si `is_paid === true`
  - ⚠️ **Pendiente** (amarillo): Si `is_paid === false` (caso histórico anterior al fix)

#### **3.2 Información del Cliente**
- Nombre
- Identificación (si existe y no es "N/A")

#### **3.3 Detalles de Estadía** (si hay fechas)
- Check-in (fecha)
- Check-out (fecha)
- Cantidad de huéspedes

#### **3.4 Información Financiera**

Grid de 2 columnas:

| Campo | Condición | Color |
|-------|-----------|-------|
| **Total Hospedaje** | Siempre | Gris |
| **Abonos** | Siempre | Verde |
| **Consumos** | Si `has_consumptions === true` | Azul |
| **Pendiente** | Si `is_paid === false` | Amarillo |

**Nota:** Después del fix de `releaseRoom()`, `is_paid` siempre será `true` porque `pending_amount` siempre es `0`.

#### **3.5 Footer de la Card**
- **Liberado por:** Nombre del usuario que liberó
- **Estado posterior:** Badge con estado de habitación después de liberar:
  - `free_clean` → "Limpia" (verde)
  - `pending_cleaning` → "Pendiente aseo" (amarillo)
  - Otros → Estado literal (gris)

### **4. Estado Vacío**

Si `total_releases === 0`:
- Icono de reloj
- Mensaje: "Sin liberaciones registradas"
- Descripción con fecha

### **5. Footer**

- **Botón "Cerrar"** (gris)
- Llama a `$wire.closeRoomDailyHistory()`

---

## 🔐 Cálculo de Estado (`is_paid`)

```php
$isPaid = (float)$release->pending_amount <= 0.01; // Tolerancia para floats
```

**Regla:**
- ✅ **Pagado** si `pending_amount <= 0.01`
- ⚠️ **Pendiente** si `pending_amount > 0.01`

**Después del fix de `releaseRoom()`:**
- `pending_amount` siempre es `0` en nuevas liberaciones
- `is_paid` siempre será `true`
- Las liberaciones antiguas (antes del fix) pueden tener `pending_amount > 0` y aparecer como "Pendiente"

---

## 🚪 Cierre del Modal

El modal se cierra de **2 formas**:

### **1. Botón X (header)**
```blade
@click="$wire.closeRoomDailyHistory()"
```

### **2. Botón "Cerrar" (footer)**
```blade
@click="$wire.closeRoomDailyHistory()"
```

**También cierra desde backdrop:**
```blade
<div @click="$wire.closeRoomDailyHistory()" class="..."></div>
```

**Método Livewire:**
```php
public function closeRoomDailyHistory(): void
{
    $this->roomDailyHistoryModal = false;
    $this->roomDailyHistoryData = null;
}
```

---

## 📊 Consulta de Datos

### **Tabla:** `room_release_history`

### **Query:**
```php
RoomReleaseHistory::where('room_id', $roomId)
    ->whereDate('release_date', $date)
    ->with('releasedBy')
    ->orderBy('created_at', 'asc')
    ->get();
```

### **Campos utilizados:**

| Campo | Uso en Modal |
|-------|--------------|
| `id` | Identificador único |
| `created_at` | Para mostrar hora (`H:i`) |
| `customer_name` | Nombre del cliente |
| `customer_identification` | Identificación |
| `guests_count` | Cantidad de huéspedes |
| `total_amount` | Total del hospedaje |
| `deposit` | Total abonado |
| `consumptions_total` | Total de consumos |
| `pending_amount` | **Estado de pago** (debe ser 0) |
| `check_in_date` | Fecha de check-in |
| `check_out_date` | Fecha de check-out |
| `target_status` | Estado posterior |
| `releasedBy` (relación) | Usuario que liberó |

---

## 🔍 Validaciones

### **Frontend:**
- Muestra estado vacío si `total_releases === 0`
- No hay validaciones de formulario (es solo lectura)

### **Backend:**
- Valida que la habitación existe
- Maneja errores con try-catch y logging
- Retorna array vacío si no hay liberaciones

---

## 🎨 Estilos CSS

### **Colores dinámicos:**

- **Pagado:** `bg-emerald-50`, `text-emerald-700`
- **Pendiente:** `bg-amber-50`, `text-amber-700`
- **Abonos:** `text-emerald-600`
- **Consumos:** `text-blue-600`

### **Timeline visual:**
- Línea vertical izquierda (`border-l-2 border-gray-200`)
- Marcador circular azul (`bg-blue-600 rounded-full`)

---

## 💡 Integración con Livewire

### **Propiedades Livewire:**

```php
public bool $roomDailyHistoryModal = false;
public ?array $roomDailyHistoryData = null;
```

### **Métodos Livewire:**

| Método | Cuándo | Parámetros |
|--------|--------|------------|
| `openRoomDailyHistory()` | Al abrir | `$roomId` |
| `closeRoomDailyHistory()` | Al cerrar | Ninguno |

### **Integración en `room-manager.blade.php`:**

```blade
x-data="{ 
    ...
    roomDailyHistoryModal: @entangle('roomDailyHistoryModal'),
    ...
}"
```

```blade
<x-room-manager.room-daily-history-modal 
    :roomDailyHistoryData="$roomDailyHistoryData" 
/>
```

---

## 🔄 Flujo Completo

```
1. Usuario → Click "Historial del día"
   ↓
2. wire:click="openRoomDailyHistory({{ $room->id }})"
   ↓
3. RoomManager::openRoomDailyHistory($roomId)
   ↓
4. Consulta room_release_history filtrado por room_id y release_date
   ↓
5. Prepara roomDailyHistoryData con todas las liberaciones
   ↓
6. roomDailyHistoryModal = true
   ↓
7. Alpine.js muestra modal (x-show)
   ↓
8. Renderiza timeline con todas las liberaciones
   ↓
9. Usuario → Click "Cerrar"
   ↓
10. $wire.closeRoomDailyHistory()
   ↓
11. Modal se cierra y datos se limpian
```

---

## ⚠️ Casos Especiales

### **1. Sin liberaciones en el día**
- Muestra estado vacío
- Mensaje: "Sin liberaciones registradas"

### **2. Múltiples liberaciones**
- Muestra todas en orden cronológico
- Cada una en su propia card

### **3. Liberaciones antiguas (antes del fix)**
- Pueden tener `pending_amount > 0`
- Aparecerán como "Pendiente"
- **Nota:** Esto es correcto, refleja el estado real al momento de liberar

### **4. Liberaciones nuevas (después del fix)**
- `pending_amount` siempre es `0`
- Siempre aparecen como "Pagado"
- **Refleja cuenta cerrada correctamente**

---

## 📚 Referencias

- **Componente:** `resources/views/components/room-manager/room-daily-history-modal.blade.php`
- **Livewire:** `app/Livewire/RoomManager.php::openRoomDailyHistory()`
- **Livewire:** `app/Livewire/RoomManager.php::closeRoomDailyHistory()`
- **Modelo:** `app/Models/RoomReleaseHistory.php`
- **Botón:** `resources/views/components/room-manager/room-actions-menu.blade.php` (línea 118)
- **Documentación relacionada:** `docs/LIBERACION_HABITACIONES.md`

---

## 🎯 Reglas de Negocio

### **1. Estado de Pago**

**Regla:** Toda liberación debe quedar **siempre pagada**

- `pending_amount` en `room_release_history` debe ser `0`
- `is_paid` debe ser `true`
- Si aparece "Pendiente", es un registro histórico antiguo (antes del fix)

### **2. Inmutabilidad**

**Regla:** El historial es **inmutable** (auditoría)

- Los snapshots JSON no cambian después de crearse
- `pending_amount` refleja el estado al momento de liberar
- No se debe modificar el historial existente

### **3. Orden Cronológico**

**Regla:** Liberaciones ordenadas por `created_at` ascendente

- Primera liberación del día aparece primero
- Última liberación del día aparece al final

---

## 🔧 Mantenimiento

### **Si necesitas cambiar:**
- **Orden:** Modificar `orderBy('created_at', 'asc')` en `openRoomDailyHistory()`
- **Formato de fecha:** Modificar `format('d/m/Y')` en `date_formatted`
- **Formato de hora:** Modificar `format('H:i')` en `released_at`
- **Criterio de "Pagado":** Modificar `<= 0.01` en cálculo de `is_paid`

---

**Última actualización:** 2026-01-18
