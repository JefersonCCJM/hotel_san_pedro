# Flujo de Transición: Habitación Arrendada → Día Siguiente

## 📋 Resumen Ejecutivo

Cuando una habitación está **arrendada** (ocupada) y el sistema pasa al **día siguiente**, el estado de la habitación se recalcula dinámicamente basándose en:

1. **Stay activa** (`stays` table) que intersecta la nueva fecha
2. **Reservation** (`reservations` + `reservation_rooms`) que cubre la nueva fecha
3. **Estado de limpieza** (`last_cleaned_at`) para fechas históricas vs actuales

**IMPORTANTE**: El sistema **NO guarda** el estado de ocupación en `rooms.status`. En su lugar, el estado se **calcula en tiempo real** usando `RoomAvailabilityService`.

---

## 🔄 Flujo Completo: Día 1 → Día 2

### **Escenario Base**
- **Habitación**: #201
- **Check-in**: 2026-01-18
- **Check-out**: 2026-01-20
- **Stay creada**: `check_in_at = 2026-01-18 14:00:00`, `check_out_at = NULL`, `status = 'active'`
- **Usuario navega**: Día 2026-01-18 → Día 2026-01-19

---

## 📍 Paso 1: Usuario Cambia de Fecha

### **Acción del Usuario**
```javascript
// En RoomManager.php: nextDay()
$this->date = $this->date->copy()->addDay();  // 2026-01-18 → 2026-01-19
$this->currentDate = $this->date;
$this->loadRooms();  // ✅ Fuerza recarga de habitaciones
$this->dispatch('room-view-changed', date: $this->date->toDateString());
```

**Código**: `app/Livewire/RoomManager.php:633-641`

---

## 📍 Paso 2: Sistema Recalcula Estado (Render)

### **Livewire re-renderiza el componente**
```php
// En RoomManager.php: render()
public function render()
{
    $rooms = $this->getRoomsQuery()->paginate(20);
    
    // Cada habitación calcula su estado usando RoomAvailabilityService
    // NO se guarda en BD, se calcula en tiempo real
}
```

**Código**: `app/Livewire/RoomManager.php:543+`

---

## 📍 Paso 3: RoomAvailabilityService Calcula Estado

### **Método Principal: `getStayForDate($date)`**

**Archivo**: `app/Services/RoomAvailabilityService.php:64-119`

#### **Regla de Negocio**
Una habitación está **OCUPADA** en una fecha `D` si y solo si:
- `check_in_at < endOfDay(D)` → El check-in ocurrió antes de que termine el día `D`
- `check_out_at >= startOfDay(D)` → El check-out ocurrió después de que empiece el día `D` (o es `NULL`)

#### **Query SQL Generada**
```sql
SELECT * FROM stays
WHERE room_id = 201
  AND check_in_at <= '2026-01-19 23:59:59'  -- endOfDay
  AND (
    -- Caso 1: check_out_at existe y es >= startOfDay
    (check_out_at IS NOT NULL AND check_out_at >= '2026-01-19 00:00:00')
    OR
    -- Caso 2: check_out_at es NULL, usar reservation_rooms.check_out_date
    (
      check_out_at IS NULL
      AND EXISTS (
        SELECT 1 FROM reservations
        INNER JOIN reservation_rooms ON reservations.id = reservation_rooms.reservation_id
        WHERE reservation_rooms.room_id = 201
          AND reservation_rooms.check_out_date >= '2026-01-19'  -- startOfDay
      )
    )
  )
ORDER BY check_in_at DESC
LIMIT 1;
```

#### **Resultado para Día 2 (2026-01-19)**
```
✅ Stay encontrada:
   - check_in_at: 2026-01-18 14:00:00  (← antes de endOfDay de 2026-01-19)
   - check_out_at: NULL                (← usa reservation_rooms.check_out_date = 2026-01-20 >= 2026-01-19)
   → Estado: OCUPADA ✅
```

---

## 📍 Paso 4: Determinación del Estado de Display

### **Método: `getDisplayStatusOn($date)`**

**Archivo**: `app/Services/RoomAvailabilityService.php:208-244`

#### **Prioridad de Estados (de mayor a menor)**

1. **MANTENIMIENTO** → Si `room.maintenance_blocks` tiene bloqueo activo
2. **OCUPADA** → Si `getStayForDate($date)` retorna un Stay (✅ nuestro caso)
3. **PENDIENTE_CHECKOUT** → Si hubo ocupación ayer y `check_out_at` es hoy
4. **SUCIA** → Si `cleaningStatus()['code'] === 'pendiente'`
5. **RESERVADA** → Si hay `reservation_rooms` con `check_in_date > endOfDay($date)`
6. **LIBRE** → Estado por defecto

#### **Para nuestro ejemplo (Día 2: 2026-01-19)**
```php
// Priority 1: Maintenance? NO
if ($this->room->isInMaintenance()) { return MANTENIMIENTO; }  // ❌

// Priority 2: Active stay? SÍ ✅
if ($this->isOccupiedOn($date)) { return OCUPADA; }  // ✅ RETORNA AQUÍ

// No se evalúan los demás estados
```

**Resultado**: `RoomDisplayStatus::OCUPADA` ✅

---

## 📍 Paso 5: Estado de Limpieza

### **Método: `cleaningStatus($date)`**

**Archivo**: `app/Models/Room.php:192-207`

#### **Lógica Diferenciada por Tipo de Fecha**

##### **A) Fecha Pasada (Histórica)**
```php
if ($isPastDate) {
    return $this->calculateHistoricalCleaningStatus($date);
}
```

**Regla**: 
- Si hubo Stay activa ese día → **LIMPIA** (se considera limpia durante ocupación)
- Si no hubo Stay → Usa `last_cleaned_at` histórico

##### **B) Fecha Actual o Futura**
```php
return $this->calculateCurrentCleaningStatus($date);
```

**Regla**: 
- Si habitación está OCUPADA y `last_cleaned_at < 24 horas` → **LIMPIA**
- Si habitación está OCUPADA y `last_cleaned_at >= 24 horas` → **PENDIENTE**
- Si habitación está LIBRE → **LIMPIA** (no aplica regla de 24h)

---

## 🎯 Ejemplo Completo: Transición 2026-01-18 → 2026-01-19

### **Estado en BD (No cambia al cambiar de fecha)**

| Tabla | Campo | Valor Día 1 | Valor Día 2 |
|-------|-------|-------------|-------------|
| `stays` | `check_in_at` | `2026-01-18 14:00:00` | ✅ **Sin cambio** |
| `stays` | `check_out_at` | `NULL` | ✅ **Sin cambio** |
| `stays` | `status` | `'active'` | ✅ **Sin cambio** |
| `reservation_rooms` | `check_in_date` | `2026-01-18` | ✅ **Sin cambio** |
| `reservation_rooms` | `check_out_date` | `2026-01-20` | ✅ **Sin cambio** |
| `rooms` | `status` | `'ocupada'` (opcional) | ✅ **Sin cambio** |

**✅ CRÍTICO**: Los datos en BD **NO cambian** al navegar entre días.

---

### **Estado Calculado (Cambia dinámicamente)**

| Métrica | Día 1 (2026-01-18) | Día 2 (2026-01-19) |
|---------|---------------------|-------------------|
| `getStayForDate()` | ✅ Stay encontrada | ✅ **Stay encontrada** |
| `isOccupiedOn()` | ✅ `true` | ✅ **`true`** |
| `getDisplayStatusOn()` | `OCUPADA` | ✅ **`OCUPADA`** |
| `cleaningStatus()` | `'limpia'` o `'pendiente'` | ✅ **Recalculado** |

**✅ CRÍTICO**: El estado se **recalcula** cada vez que se consulta una fecha diferente.

---

## 🔍 ¿Qué Sucede si el Check-out es HOY?

### **Escenario: Check-out en Día 2**

| Campo | Valor |
|-------|-------|
| `check_in_date` | `2026-01-18` |
| `check_out_date` | `2026-01-19` ← **Termina hoy** |
| `check_in_at` | `2026-01-18 14:00:00` |
| `check_out_at` | `NULL` o `2026-01-19 12:00:00` |

#### **Query en `getStayForDate(2026-01-19)`**

```sql
WHERE check_in_at <= '2026-01-19 23:59:59'  ✅
  AND (
    check_out_at >= '2026-01-19 00:00:00'   ✅ (si existe)
    OR
    check_out_at IS NULL AND reservation_rooms.check_out_date >= '2026-01-19'  ✅
  )
```

**Resultado**: ✅ Stay encontrada → **OCUPADA**

**Razón**: El día 2026-01-19 aún **intersecta** el intervalo de ocupación, aunque el check-out ocurra ese mismo día.

---

## 📊 Comparación: Día Check-out vs Día Post Check-out

### **Día Check-out (2026-01-19)**
```
check_in_at: 2026-01-18 14:00:00  (antes de endOfDay: 2026-01-19 23:59:59) ✅
check_out_at: NULL o 2026-01-19 12:00:00  (>= startOfDay: 2026-01-19 00:00:00) ✅
→ Stay encontrada → OCUPADA ✅
```

### **Día Post Check-out (2026-01-20)**
```
check_in_at: 2026-01-18 14:00:00  (antes de endOfDay: 2026-01-20 23:59:59) ✅
check_out_at: NULL o 2026-01-19 12:00:00  (< startOfDay: 2026-01-20 00:00:00) ❌
→ NO se encuentra Stay → LIBRE (o PENDIENTE_CHECKOUT si termina hoy)
```

**CRÍTICO**: Si `check_out_at` existe y es `2026-01-19 12:00:00`, entonces en el día 2026-01-20:
- `check_out_at (2026-01-19 12:00:00) < startOfDay(2026-01-20 00:00:00)` → ❌ No intersecta
- `getStayForDate(2026-01-20)` retorna `null`
- Estado: **LIBRE** (o **PENDIENTE_CHECKOUT** si `check_out_at` es hoy)

---

## 🔄 Cambios de Estado al Pasar al Día Siguiente

### **1. Si la Estancia Continúa (check-out futuro)**
```
Día N: OCUPADA
Día N+1: OCUPADA ✅ (mismo estado, recalculado)
```

### **2. Si el Check-out es HOY (día actual)**
```
Día N: OCUPADA
Día N+1: 
  - Si check_out_at ya ocurrió: LIBRE (o PENDIENTE_CHECKOUT si check_out_at = hoy)
  - Si check_out_at es NULL y check_out_date >= hoy: OCUPADA
```

### **3. Si el Check-out fue AYER**
```
Día N: OCUPADA
Día N+1: LIBRE (o PENDIENTE_CHECKOUT si check_out_at = hoy)
```

---

## 🎯 Puntos Clave del Sistema

### **✅ Arquitectura Dinámica**
- El estado **NO se guarda** en `rooms.status` para ocupación
- Se **calcula en tiempo real** usando `RoomAvailabilityService`
- Los datos en BD (`stays`, `reservations`) **permanecen inmutables**

### **✅ Consulta por Intersección de Intervalos**
- `getStayForDate($date)` busca si el intervalo `[check_in_at, check_out_at]` **intersecta** el día `D`
- Usa operadores de comparación `<` y `>=` para calcular intersecciones correctamente

### **✅ Manejo de NULL**
- Si `check_out_at IS NULL`, se usa `reservation_rooms.check_out_date` como fallback
- Esto permite manejar estancias en curso sin timestamp de checkout

### **✅ Prioridad de Estados**
- MANTENIMIENTO > OCUPADA > PENDIENTE_CHECKOUT > SUCIA > RESERVADA > LIBRE
- El primer estado que aplique **detiene la evaluación**

---

## 📝 Resumen Final

Cuando una habitación está arrendada y pasa al día siguiente:

1. **Los datos en BD NO cambian** (stays, reservations permanecen iguales)
2. **El estado se recalcula dinámicamente** usando `RoomAvailabilityService`
3. **Si el Stay intersecta el nuevo día**, la habitación sigue **OCUPADA**
4. **Si el check-out ya ocurrió**, la habitación pasa a **LIBRE** (o **PENDIENTE_CHECKOUT** si el check-out es hoy)
5. **El estado de limpieza se recalcula** según si la fecha es histórica o actual

**✅ El sistema es reactivo y dinámico**: el cambio de fecha solo afecta la **consulta** de estado, no los **datos almacenados**.
