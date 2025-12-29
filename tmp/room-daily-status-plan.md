## Estado actual
- Estados diarios se calculan en tiempo real con reservas; no hay snapshots históricos.
- Cambiar de fecha reutiliza lógica actual, sin fuente persistente para días pasados.

## Estado final
- Tabla `room_daily_statuses` con snapshot por habitación y día (estado, limpieza, reserva, huésped, monto).
- Comando programado que genera snapshot diario (día anterior).
- RoomManager usa snapshots para fechas pasadas, lógica actual para hoy, reservas para futuro.

## Archivos a modificar
- Migration nueva para `room_daily_statuses`.
- Modelo `RoomDailyStatus` (nuevo).
- Comando `rooms:snapshot` y scheduler (`app/Console/Kernel.php`).
- `app/Livewire/RoomManager.php` (lectura desde snapshot en fechas pasadas).

## Tareas
1) Crear migration + modelo `RoomDailyStatus`.
2) Implementar comando `rooms:snapshot` con lógica de snapshot día anterior.
3) Agendar en `Kernel` dailyAt 00:05.
4) Ajustar `RoomManager` render: pasado → snapshot; hoy → tiempo real; futuro → reservas.

