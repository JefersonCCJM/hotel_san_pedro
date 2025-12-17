Estado actual
- CustomerController tiene lógica duplicada de perfil fiscal y carga de catálogos DIAN repartida entre acciones.
- Hay validaciones en Form Requests, pero el controlador recalcula flags y maneja respuestas Ajax y web.

Estado final
- Controlador limpio sin referencias a módulos eliminados.
- Lógica de perfil fiscal centralizada y reutilizable.
- Carga de catálogos en un único helper privado.
- Acciones CRUD y endpoints JSON intactos.

Archivos a modificar
- app/Http/Controllers/CustomerController.php

Tareas
- Revisar CustomerController para detectar duplicados y código muerto.
- Extraer helpers para catálogos DIAN y sincronización de perfil fiscal.
- Ajustar flags booleanos y limpiar imports/comentarios obsoletos.

