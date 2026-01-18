# 📋 Contexto: Creación de Habitaciones

## 🎯 Resumen Ejecutivo

Este documento explica cómo se guardan las habitaciones en el sistema, desde el formulario del modal `create-room-modal` hasta la persistencia en la base de datos.

**Componentes Involucrados:**
- Vista: `resources/views/components/room-manager/create-room-modal.blade.php`
- Livewire: `app/Livewire/CreateRoom.php`
- Modelo: `app/Models/Room.php`
- Modelo: `app/Models/RoomRate.php`

---

## 📊 Tablas de Base de Datos

### 1. `rooms` (Tabla Principal)

**Estructura Final (Después de todas las migraciones):**

```sql
CREATE TABLE rooms (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(255) UNIQUE NOT NULL,           -- Número de habitación
    room_type_id BIGINT NULL,                           -- FK a room_types (opcional)
    ventilation_type_id BIGINT NOT NULL,                -- FK a ventilation_types (obligatorio)
    beds_count INT DEFAULT 1,                           -- Número de camas (1-15)
    max_capacity INT DEFAULT 2,                         -- Capacidad máxima de huéspedes
    base_price_per_night DECIMAL(12,2) NULL,            -- Precio base por noche (fallback)
    is_active BOOLEAN DEFAULT true,                     -- Habitación activa/inactiva
    last_cleaned_at TIMESTAMP NULL,                     -- Última fecha de limpieza
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (room_type_id) REFERENCES room_types(id),
    FOREIGN KEY (ventilation_type_id) REFERENCES ventilation_types(id)
);
```

**Columnas Clave:**
- `room_number`: **ÚNICO** y **OBLIGATORIO** (validación `unique:rooms,room_number`)
- `beds_count`: Mínimo 1, máximo 15
- `max_capacity`: Mínimo 1
- `last_cleaned_at`: Se establece en `now()` al crear (nueva habitación = limpia)

---

### 2. `room_rates` (Precios por Ocupación)

**Estructura Actual (Después de migración `2026_01_04_150000_update_room_rates_schema.php`):**

```sql
CREATE TABLE room_rates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    room_id BIGINT NOT NULL,                            -- FK a rooms
    min_guests INT DEFAULT 1,                           -- Mínimo de huéspedes
    max_guests INT DEFAULT 1,                           -- Máximo de huéspedes (generalmente = min_guests)
    price_per_night DECIMAL(12,2) DEFAULT 0,            -- Precio por noche para este rango
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
```

**Cambio Arquitectónico:**
- **ANTES**: `occupancy_prices JSON` (estructura flexible pero difícil de consultar)
- **AHORA**: Múltiples registros `RoomRate` (uno por cada ocupación con precio)

**Ejemplo:**
```
Habitación 101:
- RoomRate 1: min_guests=1, max_guests=1, price_per_night=60000
- RoomRate 2: min_guests=2, max_guests=2, price_per_night=80000
- RoomRate 3: min_guests=3, max_guests=3, price_per_night=100000
```

---

### 3. `room_types` (Catálogo de Tipos)

```sql
CREATE TABLE room_types (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE NOT NULL,                  -- Código único
    name VARCHAR(255) NOT NULL,                         -- Nombre del tipo
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Ejemplo:**
- `code: "simple"`, `name: "Habitación Simple"`
- `code: "doble"`, `name: "Habitación Doble"`
- `code: "suite"`, `name: "Suite"`

---

### 4. `ventilation_types` (Catálogo de Ventilación)

```sql
CREATE TABLE ventilation_types (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE NOT NULL,                  -- Código único
    name VARCHAR(255) NOT NULL,                         -- Nombre del tipo
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Ejemplo:**
- `code: "natural"`, `name: "Ventilación Natural"`
- `code: "aire_acondicionado"`, `name: "Aire Acondicionado"`

---

## 🔄 Flujo de Guardado

### **Paso 1: Usuario Completa el Formulario**

El modal `create-room-modal.blade.php` renderiza el componente Livewire `<livewire:create-room />`.

**Propiedades del Componente Livewire (`CreateRoom.php`):**
```php
public string $room_number = '';              // Número de habitación
public int $beds_count = 1;                   // Número de camas (default: 1)
public int $max_capacity = 2;                 // Capacidad máxima (default: 2)
public bool $auto_calculate = true;           // Auto-calcular capacidad = beds_count * 2
public ?int $room_type = null;                // ID de room_type (opcional)
public ?int $ventilation_type = null;         // ID de ventilation_type (obligatorio)
public array $occupancy_prices = [];          // Array: [1 => 60000, 2 => 80000, ...]
public float $base_price_per_night = 0.0;     // Precio base (fallback)
public bool $is_active = true;                // Habitación activa (default: true)
```

---

### **Paso 2: Validación (`CreateRoom::store()`)**

**Reglas de Validación:**
```php
[
    'room_number' => 'required|string|unique:rooms,room_number',
    'room_type' => 'nullable|integer|exists:room_types,id',
    'ventilation_type' => 'required|integer|exists:ventilation_types,id',
    'beds_count' => 'required|integer|min:1|max:15',
    'max_capacity' => 'required|integer|min:1',
    'base_price_per_night' => 'nullable|numeric|min:0',
    'is_active' => 'boolean',
]
```

**Validaciones Adicionales:**
1. **Al menos un precio de ocupación**: Debe haber al menos un valor `> 0` en `occupancy_prices`
2. **Prevención de doble envío**: Flag `$isProcessing` previene múltiples llamadas

---

### **Paso 3: Crear Habitación en `rooms`**

**Código (`CreateRoom::store()`):**
```php
$room = Room::create([
    'room_number' => $this->room_number,
    'room_type_id' => $this->room_type,                    // Puede ser NULL
    'ventilation_type_id' => $this->ventilation_type,      // OBLIGATORIO
    'beds_count' => $this->beds_count,
    'max_capacity' => $this->max_capacity,
    'base_price_per_night' => $this->base_price_per_night,
    'is_active' => $this->is_active,
    'last_cleaned_at' => now(),                            // ✅ Nueva habitación = limpia
]);
```

**Nota:** `last_cleaned_at = now()` indica que la habitación está limpia al momento de creación.

---

### **Paso 4: Crear Tarifas en `room_rates`**

**Código (`CreateRoom::store()`):**
```php
// Filtrar solo precios válidos (> 0)
$validatedPrices = [];
foreach ($this->occupancy_prices as $minGuests => $price) {
    if ($value !== null && $value > 0) {
        $validatedPrices[$minGuests] = (int)$value;
    }
}

// Crear un RoomRate por cada ocupación con precio
foreach ($validatedPrices as $minGuests => $price) {
    RoomRate::create([
        'room_id' => $room->id,
        'min_guests' => $minGuests,                        // Ej: 1, 2, 3
        'max_guests' => $minGuests,                        // ✅ Generalmente igual a min_guests
        'price_per_night' => $price,                       // Precio para esa ocupación
    ]);
}
```

**Ejemplo Real:**
```php
// Si occupancy_prices = [1 => 60000, 2 => 80000, 3 => 100000]

// Se crean 3 registros en room_rates:
RoomRate::create(['room_id' => 1, 'min_guests' => 1, 'max_guests' => 1, 'price_per_night' => 60000]);
RoomRate::create(['room_id' => 1, 'min_guests' => 2, 'max_guests' => 2, 'price_per_night' => 80000]);
RoomRate::create(['room_id' => 1, 'min_guests' => 3, 'max_guests' => 3, 'price_per_night' => 100000]);
```

---

### **Paso 5: Resetear Formulario y Emitir Eventos**

**Código:**
```php
// Resetear formulario
$this->room_number = '';
$this->beds_count = 1;
$this->max_capacity = 2;
$this->auto_calculate = true;
$this->room_type = null;
$this->ventilation_type = null;
$this->occupancy_prices = [];

// Re-inicializar capacidad
$this->updateCapacity();

// Emitir eventos
$this->dispatch('room-created', roomId: $room->id);
$this->dispatch('notify', type: 'success', message: 'Habitación creada exitosamente.');
```

---

## 🔗 Relaciones Eloquent

### **Room Model (`app/Models/Room.php`)**

```php
// Relaciones
public function RoomType()              // belongsTo(RoomType::class)
public function VentilationType()       // belongsTo(VentilationType::class)
public function rates()                 // hasMany(RoomRate::class)
public function reservations()          // belongsToMany(Reservation::class, 'reservation_rooms')
public function reservationRooms()      // hasMany(ReservationRoom::class)
public function stays()                 // hasMany(Stay::class)
public function maintenanceBlocks()     // hasMany(RoomMaintenanceBlock::class)
```

### **RoomRate Model (`app/Models/RoomRate.php`)**

```php
// Relación
public function room()                  // belongsTo(Room::class)
```

---

## 📝 Ejemplo Completo

### **Input del Usuario:**
```
room_number: "101"
beds_count: 2
max_capacity: 4
auto_calculate: true (max_capacity = 2 * 2 = 4)
room_type: 1 (ID de "Habitación Doble")
ventilation_type: 2 (ID de "Aire Acondicionado")
occupancy_prices: [
    1 => 60000,
    2 => 80000,
    3 => 90000,
    4 => 100000
]
base_price_per_night: 60000
is_active: true
```

### **Resultado en Base de Datos:**

**Tabla `rooms`:**
```
id: 1
room_number: "101"
room_type_id: 1
ventilation_type_id: 2
beds_count: 2
max_capacity: 4
base_price_per_night: 60000
is_active: 1 (true)
last_cleaned_at: "2026-01-14 12:00:00"
created_at: "2026-01-14 12:00:00"
updated_at: "2026-01-14 12:00:00"
```

**Tabla `room_rates` (4 registros):**
```
id: 1, room_id: 1, min_guests: 1, max_guests: 1, price_per_night: 60000
id: 2, room_id: 1, min_guests: 2, max_guests: 2, price_per_night: 80000
id: 3, room_id: 1, min_guests: 3, max_guests: 3, price_per_night: 90000
id: 4, room_id: 1, min_guests: 4, max_guests: 4, price_per_night: 100000
```

---

## ⚠️ Validaciones Importantes

1. **`room_number` debe ser ÚNICO**: Laravel valida `unique:rooms,room_number`
2. **`ventilation_type` es OBLIGATORIO**: No puede ser `null`
3. **`beds_count`**: Mínimo 1, máximo 15
4. **`max_capacity`**: Mínimo 1
5. **Al menos un precio de ocupación**: Debe haber al menos un valor `> 0` en `occupancy_prices`
6. **Prevención de doble envío**: Flag `$isProcessing` evita múltiples llamadas simultáneas

---

## 🔍 Lógica de Precios

### **Cálculo Automático de Capacidad**

```php
private function updateCapacity(): void
{
    if ($this->auto_calculate && isset($this->beds_count) && $this->beds_count > 0) {
        $this->max_capacity = $this->beds_count * 2;  // 2 personas por cama
    }
    
    $this->initializePrices();  // Inicializa array de precios
}
```

### **Inicialización de Precios**

```php
private function initializePrices(): void
{
    $newPrices = [];
    for ($i = 1; $i <= $this->max_capacity; $i++) {
        // Preserva valores existentes si existen, sino hereda del anterior
        $existingValue = $this->occupancy_prices[$i] ?? null;
        $previousValue = $this->occupancy_prices[$i - 1] ?? null;

        if ($existingValue !== null && $existingValue > 0) {
            $newPrices[$i] = $existingValue;
        } elseif ($previousValue !== null && $previousValue > 0) {
            $newPrices[$i] = $previousValue;  // Hereda del anterior
        } else {
            $newPrices[$i] = null;  // Placeholder vacío
        }
    }
    $this->occupancy_prices = $newPrices;
}
```

**Ejemplo:**
```
Si max_capacity = 4 y usuario define:
- occupancy_prices[1] = 60000
- occupancy_prices[2] = 80000
- occupancy_prices[3] = (vacío)
- occupancy_prices[4] = 100000

Resultado:
- occupancy_prices[1] = 60000  ✅ Preservado
- occupancy_prices[2] = 80000  ✅ Preservado
- occupancy_prices[3] = 80000  ✅ Heredado de [2]
- occupancy_prices[4] = 100000 ✅ Preservado
```

---

## 🎯 Casos de Uso

### **Caso 1: Habitación Simple (1 cama, 2 personas)**

```
beds_count: 1
max_capacity: 2
occupancy_prices: [1 => 50000, 2 => 60000]
```

**Resultado:**
- 1 registro en `rooms`
- 2 registros en `room_rates` (1 huésped y 2 huéspedes)

---

### **Caso 2: Habitación Grande (3 camas, 6 personas)**

```
beds_count: 3
max_capacity: 6
occupancy_prices: [1 => 60000, 2 => 80000, 3 => 90000, 4 => 100000, 5 => 110000, 6 => 120000]
```

**Resultado:**
- 1 registro en `rooms`
- 6 registros en `room_rates` (uno por cada ocupación)

---

### **Caso 3: Solo Tipo de Ventilación (sin tipo de habitación)**

```
room_type: null  ✅ Permitido
ventilation_type: 2  ✅ Obligatorio
```

**Resultado:**
- `room_type_id` será `NULL` en la base de datos
- `ventilation_type_id` será `2`

---

## 📚 Referencias

- **Componente Livewire**: `app/Livewire/CreateRoom.php`
- **Modelo Room**: `app/Models/Room.php`
- **Modelo RoomRate**: `app/Models/RoomRate.php`
- **Vista Modal**: `resources/views/components/room-manager/create-room-modal.blade.php`
- **Migración Inicial**: `database/migrations/2025_12_17_185427_create_rooms_table.php`
- **Migración Tarifas**: `database/migrations/2025_12_18_131249_create_room_rates_table.php`
- **Migración Actualización Tarifas**: `database/migrations/2026_01_04_150000_update_room_rates_schema.php`
- **Migración Tipos**: `database/migrations/2026_01_04_145000_update_rooms_schema_with_types.php`

---

## ✅ Resumen de Tablas Relacionadas

| Tabla | Propósito | Relación con `rooms` |
|-------|-----------|---------------------|
| `rooms` | Habitación principal | - |
| `room_rates` | Precios por ocupación | `room_id` → `rooms.id` (CASCADE) |
| `room_types` | Catálogo de tipos | `rooms.room_type_id` → `room_types.id` (nullable) |
| `ventilation_types` | Catálogo de ventilación | `rooms.ventilation_type_id` → `ventilation_types.id` (required) |
| `reservation_rooms` | Reservas asignadas | `room_id` → `rooms.id` |
| `stays` | Ocupaciones reales | `room_id` → `rooms.id` |

---

**Última actualización:** 2026-01-14
