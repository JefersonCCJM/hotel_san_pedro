# Plan de Implementación: Módulo Externo de Estados de Habitaciones

## Estado Actual

### Enum RoomStatus existente:
- LIBRE
- RESERVADA
- OCUPADA
- MANTENIMIENTO
- LIMPIEZA

### Estados requeridos por el usuario:
- LIBRE (Verde) - Disponible para arrendar
- OCUPADA (Azul) - Huésped activo
- SUCIA (Rojo) - Requiere limpieza
- PENDIENTE CHECKOUT (Morado) - Huésped no confirma salida

### Mapeo:
- LIMPIEZA → SUCIA (equivalente)
- Necesitamos agregar: PENDIENTE_CHECKOUT

## Estado Final

### Nuevo Enum RoomStatus:
- LIBRE
- RESERVADA
- OCUPADA
- MANTENIMIENTO
- LIMPIEZA (mantener para compatibilidad, pero usar SUCIA)
- SUCIA (nuevo)
- PENDIENTE_CHECKOUT (nuevo)

### Módulo Externo:
- Ruta pública: `/public/rooms/status`
- Vista: `resources/views/public/room-status.blade.php`
- Controlador: `app/Http/Controllers/PublicRoomStatusController.php`
- Endpoint: `POST /public/rooms/{room}/mark-clean`

## Archivos a Modificar/Crear

### 1. Actualizar Enum
- `app/Enums/RoomStatus.php` - Agregar SUCIA y PENDIENTE_CHECKOUT

### 2. Crear Controlador Público
- `app/Http/Controllers/PublicRoomStatusController.php` - Nuevo

### 3. Crear Rutas Públicas
- `routes/web.php` - Agregar rutas públicas sin autenticación

### 4. Crear Vista Externa
- `resources/views/public/room-status.blade.php` - Nueva vista responsive

### 5. Migración (si es necesario)
- Verificar si necesitamos migración para actualizar valores existentes

## Reglas de Negocio

### Transiciones válidas:
- SUCIA → LIBRE (única transición permitida desde el módulo externo)

### Validaciones backend:
1. La habitación debe existir
2. El estado actual DEBE ser SUCIA
3. No debe haber reservaciones activas que bloqueen la liberación
4. Rate limiting para prevenir abusos

### Lógica de PENDIENTE_CHECKOUT:
- Se determina cuando:
  - Hay una reserva que termina HOY (check_out_date = hoy)
  - Y la habitación aún no ha sido marcada como SUCIA o LIBRE
  - O cuando hay una reserva activa pero el checkout no está confirmado

## Lista de Tareas

1. ✅ Actualizar RoomStatus enum con SUCIA y PENDIENTE_CHECKOUT
2. ✅ Crear PublicRoomStatusController con método index() y markClean()
3. ✅ Agregar rutas públicas en web.php
4. ✅ Crear vista pública responsive con Tailwind + Alpine
5. ✅ Implementar validaciones estrictas en markClean()
6. ✅ Agregar rate limiting a las rutas públicas
7. ✅ Probar flujo completo

