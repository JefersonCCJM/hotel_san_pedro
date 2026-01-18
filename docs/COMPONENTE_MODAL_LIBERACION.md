# 🧩 Contexto Completo: `room-release-confirmation-modal.blade.php`

## 📍 Ubicación
`resources/views/components/room-manager/room-release-confirmation-modal.blade.php`

---

## 🎯 Propósito

Este componente es el **modal de confirmación** que se muestra antes de liberar una habitación. Permite al usuario:

1. **Revisar información financiera** completa de la reserva
2. **Validar estado de pagos** y consumos
3. **Confirmar pago** si hay deuda pendiente
4. **Ejecutar la liberación** de manera segura

---

## 🛠️ Tecnología

- **Alpine.js** para estado local y UI reactiva
- **Eventos personalizados** (`CustomEvent`) para comunicación con Livewire
- **NO usa Livewire entangle** (es completamente independiente)

---

## 📐 Estructura Alpine.js

```javascript
x-data="{ 
    show: false,                    // Visibilidad del modal
    roomData: null,                 // Datos desde loadRoomReleaseData()
    paymentConfirmed: false,        // Checkbox: "Confirmo que se realizó el pago"
    refundConfirmed: false,         // (Reservado para devoluciones futuras)
    paymentMethod: '',              // 'efectivo' | 'transferencia' | ''
    bankName: '',                   // Si transferencia
    reference: '',                  // Si transferencia
    isLoading: false,               // Estado durante liberación
    
    resetState() {                  // Limpia todos los campos
        this.roomData = null;
        this.paymentConfirmed = false;
        this.refundConfirmed = false;
        this.paymentMethod = '';
        this.bankName = '';
        this.reference = '';
        this.isLoading = false;
    },
    
    init() {
        // Escucha evento para abrir
        window.addEventListener('open-release-confirmation', (e) => {
            this.resetState();
            this.roomData = e.detail;
            this.show = true;
        });
        
        // Escucha evento para cerrar
        window.addEventListener('close-room-release-modal', () => {
            this.show = false;
            this.resetState();
        });
    }
}"
```

---

## 🔄 Eventos que Escucha

| Evento | Origen | Propósito |
|--------|--------|-----------|
| `open-release-confirmation` | `scripts.blade.php::confirmRelease()` | Abre el modal con datos cargados |
| `close-room-release-modal` | `RoomManager::closeRoomReleaseConfirmation()` | Cierra el modal |

---

## 🚀 Cómo se Dispara

### **Paso 1: Usuario hace click en botón "Liberar"**

**Desde `room-actions-menu.blade.php`:**
```blade
<button @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', 0, null, false);">
```

### **Paso 2: Función JavaScript `confirmRelease()`**

**En `scripts.blade.php`:**
```javascript
function confirmRelease(roomId, roomNumber, totalDebt, reservationId, isCancellation = false) {
    // 1. Llama a Livewire para cargar datos
    @this.call('loadRoomReleaseData', roomId, isCancellation).then((data) => {
        // 2. Agrega flag de cancelación si aplica
        if (isCancellation) {
            data.is_cancellation = true;
        }
        
        // 3. Dispara evento para abrir modal
        window.dispatchEvent(new CustomEvent('open-release-confirmation', {
            detail: data  // ✅ Datos completos desde Livewire
        }));
    });
}
```

### **Paso 3: Modal se abre con datos**

El `init()` de Alpine.js captura el evento y asigna `roomData = e.detail`, luego `show = true`.

---

## 📦 Estructura de `roomData`

**Viene de `RoomManager::loadRoomReleaseData()`:**

```php
[
    'room_id' => $room->id,
    'room_number' => $room->room_number,
    'reservation' => [
        'id' => $reservation->id,
        'customer' => [
            'name' => 'Juan Pérez',
            'id' => 123
        ],
        // ... más datos de reserva
    ],
    'sales' => [
        ['id' => 1, 'product' => ['name' => 'Coca Cola'], 'quantity' => 2, 'total' => 4000, 'is_paid' => true],
        // ...
    ],
    'deposit_history' => [
        ['id' => 1, 'amount' => 50000, 'payment_method' => 'efectivo', 'created_at' => '2026-01-18 10:00'],
        // ...
    ],
    'refunds_history' => [],  // Array de devoluciones (si existen)
    'total_hospedaje' => 150000,
    'abono_realizado' => 100000,
    'sales_total' => 4000,
    'total_debt' => 54000,  // ⚠️ Positivo = debe, Negativo = se le debe
    'identification' => '1234567890',
    'is_cancellation' => false,
]
```

---

## 🧩 Secciones del Modal

### **1. Header**

- **Título dinámico:**
  - Si `cancel_url` o `is_cancellation`: "Cancelar Reserva - Habitación #X"
  - Si no: "Liberar Habitación #X"
- **Icono:** Puerta abierta (fa-door-open)
- **Botón cerrar (X)**

### **2. Información del Cliente** (si hay reserva)

- Nombre
- Identificación

### **3. Resumen Financiero** (si hay reserva)

Grid de 4 columnas:

| Campo | Color | Descripción |
|-------|-------|-------------|
| **Hospedaje** | Gris | `total_hospedaje` |
| **Abono Realizado** | Verde | `abono_realizado` |
| **Total Consumos** | Gris | `sales_total` |
| **Deuda / Pago Adelantado / Al Día** | Dinámico | `total_debt` |

**Lógica de color:**
- `total_debt > 0`: Rojo ("Deuda Pendiente")
- `total_debt < 0`: Azul ("Pago Adelantado")
- `total_debt === 0`: Verde ("Al Día")

### **4. Consumos** (si existen)

Tabla con:
- Producto
- Cantidad
- Estado (Pagado / Pendiente)
- Total

### **5. Historial de Abonos** (si existen)

Tabla con:
- Fecha
- Monto
- Método (badge)
- Notas

### **6. Historial de Devoluciones** (si existen)

Tabla con:
- Fecha
- Monto (azul)
- Registrado por

### **7. Validaciones Dinámicas**

#### **A) Deuda Pendiente (`total_debt > 0`)**

**Advertencia roja:**
- Muestra total hospedaje, abono realizado, saldo pendiente
- **Selector de método de pago** (obligatorio):
  - `efectivo`
  - `transferencia`
- **Si transferencia:**
  - Campo `bankName` (opcional)
  - Campo `reference` (obligatorio)
- **Checkbox:** "Confirmo que se realizó el pago de la deuda"

**Botón deshabilitado si:**
```javascript
!paymentConfirmed || !paymentMethod || (paymentMethod === 'transferencia' && !reference)
```

#### **B) Pago Adelantado (`total_debt < 0`)**

**Info azul:**
- "El cliente tiene un pago adelantado de $X"
- "La devolución solo se evalúa al finalizar la estadía"
- **Botón deshabilitado** si no hay devoluciones registradas

#### **C) Cuenta al Día (`total_debt === 0`)**

**Mensaje verde:**
- "No hay deuda pendiente. Puede proceder a liberar la habitación."
- **Botón habilitado** inmediatamente

#### **D) Sin Reserva (`!roomData.reservation`)**

**Info azul:**
- "Habitación sin reserva activa"
- "Puede proceder a liberarla"
- **Botón habilitado**

### **8. Footer - Botones**

#### **Botón "Confirmar Liberación/Cancelación"** (Verde)

**Click handler:**
```javascript
@click="
    // Validaciones
    if ((roomData.total_debt || 0) > 0) {
        if (!paymentConfirmed) return;
        if (!paymentMethod) return;
        if (paymentMethod === 'transferencia' && !reference) return;
    }
    
    isLoading = true;
    
    // Llamar a Livewire
    if ($wire) {
        $wire.call('releaseRoom', 
            roomData.room_id,
            'libre',               // target_status
            paymentMethod,         // Método de pago
            bankName,              // Banco (si transferencia)
            reference             // Referencia (si transferencia)
        ).finally(() => { 
            isLoading = false; 
        });
    }
"
```

**Estados deshabilitado:**
- `isLoading = true`
- Deuda pendiente Y no confirmó pago
- Deuda pendiente Y no seleccionó método
- Transferencia Y falta `reference`
- Pago adelantado Y no hay devoluciones

#### **Botón "Cancelar"** (Gris)

```javascript
@click="
    show = false;
    if ($wire) { $wire.call('closeRoomReleaseConfirmation'); }
"
```

---

## 🔐 Validaciones del Frontend

### **Botón "Confirmar Liberación" está deshabilitado si:**

```javascript
// Caso 1: Cargando
isLoading

// Caso 2: Hay deuda Y (no confirmó pago O no seleccionó método O falta referencia)
(total_debt > 0) && (!paymentConfirmed || !paymentMethod || (paymentMethod === 'transferencia' && !reference))

// Caso 3: Hay pago adelantado Y no se registró devolución
(total_debt < 0) && (!refunds_history || refunds_history.length === 0)
```

### **Validación en click:**

```javascript
if ((roomData.total_debt || 0) > 0) {
    if (!paymentConfirmed) return;  // Bloquea si no confirmó
    if (!paymentMethod) return;     // Bloquea si no seleccionó método
    if (paymentMethod === 'transferencia' && !reference) return;  // Bloquea si falta referencia
}
```

---

## 🔄 Flujo Completo de Liberación

```
1. Usuario → Click "Liberar"
   ↓
2. confirmRelease() → Llama a loadRoomReleaseData()
   ↓
3. Livewire responde con roomData
   ↓
4. Dispara evento 'open-release-confirmation'
   ↓
5. Modal Alpine.js se abre (show = true)
   ↓
6. Usuario revisa información
   ↓
7a. Si hay deuda:
    - Selecciona método de pago
    - Completa campos (si transferencia)
    - Marca checkbox "Confirmo pago"
   ↓
7b. Si está al día:
    - No requiere acciones
   ↓
8. Usuario → Click "Confirmar Liberación"
   ↓
9. Valida condiciones frontend
   ↓
10. isLoading = true
   ↓
11. $wire.call('releaseRoom', ...)
   ↓
12. Livewire ejecuta releaseRoom()
   ↓
13. Modal se cierra automáticamente
```

---

## 🚪 Cierre del Modal

El modal se cierra de **3 formas**:

### **1. Botón X (header)**
```javascript
@click="
    show = false;
    if ($wire) { $wire.call('closeRoomReleaseConfirmation'); }
"
```

### **2. Backdrop (overlay)**
```javascript
@click="
    show = false;
    if ($wire) { $wire.call('closeRoomReleaseConfirmation'); }
"
```

### **3. Botón "Cancelar"**
```javascript
@click="
    show = false;
    if ($wire) { $wire.call('closeRoomReleaseConfirmation'); }
"
```

**Todas llaman a:**
- `show = false` (cierra Alpine.js)
- `$wire.call('closeRoomReleaseConfirmation')` (sincroniza Livewire)

---

## 💡 Integración con Livewire

### **Métodos que se llaman:**

| Método | Cuándo | Parámetros |
|--------|--------|------------|
| `loadRoomReleaseData()` | Al abrir | `$roomId, $isCancellation` |
| `releaseRoom()` | Al confirmar | `$roomId, $status, $paymentMethod, $bankName, $reference` |
| `closeRoomReleaseConfirmation()` | Al cerrar | Ninguno |

### **Eventos que dispara:**

| Evento | Cuándo | Origen |
|--------|--------|--------|
| `open-release-confirmation` | Abrir modal | `scripts.blade.php` |
| `close-room-release-modal` | Cerrar modal | `RoomManager` |

---

## 🎨 Clases CSS Principales

- **Colores dinámicos:**
  - Deuda: `bg-red-50`, `text-red-700`
  - Pago adelantado: `bg-blue-50`, `text-blue-700`
  - Al día: `bg-emerald-50`, `text-emerald-700`

- **Estados del botón:**
  - Habilitado: `bg-emerald-600 hover:bg-emerald-700`
  - Deshabilitado: `bg-gray-400 cursor-not-allowed`

---

## 📚 Referencias

- **Componente:** `resources/views/components/room-manager/room-release-confirmation-modal.blade.php`
- **JavaScript:** `resources/views/components/room-manager/scripts.blade.php::confirmRelease()`
- **Livewire:** `app/Livewire/RoomManager.php::loadRoomReleaseData()`
- **Livewire:** `app/Livewire/RoomManager.php::releaseRoom()`
- **Documentación relacionada:** `docs/LIBERACION_HABITACIONES.md`

---

**Última actualización:** 2026-01-18
