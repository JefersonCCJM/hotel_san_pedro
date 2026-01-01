# Modal Reutilizable para Crear Cliente

## Descripción

Componente Livewire reutilizable para crear clientes desde cualquier parte de la aplicación. Incluye soporte completo para facturación electrónica DIAN.

## Archivos Creados

- `app/Livewire/CreateCustomerModal.php` - Componente Livewire
- `resources/views/livewire/create-customer-modal.blade.php` - Vista del modal

## Uso Básico

### 1. Incluir el componente en tu vista

```blade
<livewire:create-customer-modal />
```

### 2. Abrir el modal desde cualquier botón

```blade
<button @click="$dispatch('open-create-customer-modal')">
    Crear Cliente
</button>
```

### 3. Escuchar cuando se crea un cliente

```blade
<div x-data @customer-created.window="handleCustomerCreated($event.detail)">
    <!-- Tu contenido -->
</div>

<script>
function handleCustomerCreated(detail) {
    const customerId = detail.customerId;
    const customer = detail.customer; // { id, name, identification }
    console.log('Cliente creado:', customer);
    // Actualizar tu UI aquí
}
</script>
```

## Eventos Emitidos

### `customer-created`

Se emite cuando se crea exitosamente un cliente.

**Payload:**
```javascript
{
    customerId: 123,
    customer: {
        id: 123,
        name: "JUAN PÉREZ",
        identification: "12345678"
    }
}
```

### `notify`

Se emite para mostrar notificaciones (éxito/error).

**Payload:**
```javascript
{
    type: 'success', // o 'error'
    message: 'Cliente creado exitosamente.'
}
```

## Características

- ✅ Validación completa de campos
- ✅ Verificación de identificación duplicada
- ✅ Soporte para facturación electrónica DIAN
- ✅ Cálculo automático de dígito verificador (DV) para NIT
- ✅ Campos condicionales según tipo de documento
- ✅ Integración con TomSelect para municipios
- ✅ Eventos para integración con otros componentes

## Ejemplo Completo

```blade
<div>
    <button @click="$dispatch('open-create-customer-modal')" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        Crear Cliente
    </button>
    
    <livewire:create-customer-modal />
    
    <div x-data="{ selectedCustomerId: null }" 
         @customer-created.window="selectedCustomerId = $event.detail.customerId">
        <p x-show="selectedCustomerId">Cliente seleccionado: <span x-text="selectedCustomerId"></span></p>
    </div>
</div>
```

## Integración con RoomManager

El componente ya está integrado en `room-manager.blade.php` y funciona automáticamente con el select de clientes usando TomSelect.

