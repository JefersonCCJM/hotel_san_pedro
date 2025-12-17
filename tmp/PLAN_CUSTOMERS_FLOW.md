# Plan: Limpieza flujo de clientes

## Estado actual
- CustomerController coordina CRUD pero valida directamente en `saveTaxProfile`.
- Existen dos juegos de Form Requests; el controlador usa los de nivel raíz.
- La vista `customers/create.blade.php` incluye campos DIAN pero sin alineación explícita con reglas de backend.

## Estado final
- CustomerController solo orquesta y delega validaciones a Form Requests.
- Form Requests consolidados y sin duplicación innecesaria.
- Vista de creación alineada con reglas y nombres de campos requeridos por el backend.

## Archivos a modificar
- app/Http/Controllers/CustomerController.php
- app/Http/Requests/StoreCustomerRequest.php
- app/Http/Requests/UpdateCustomerRequest.php
- resources/views/customers/create.blade.php
- (opcional si se reemplaza validación): app/Http/Requests/SaveCustomerTaxProfileRequest.php

## Tareas
1. Revisar y limpiar CustomerController (imports, validaciones directas, manejo de errores).
2. Consolidar Form Requests de store/update y cubrir campos DIAN obligatorios.
3. Ajustar flujo de respuestas (redirect/json) con mensajes coherentes.
4. Alinear formulario create con validaciones y marcar campos obligatorios coherentes.

