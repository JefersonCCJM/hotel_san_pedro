{{-- 
    EJEMPLO DE USO DEL MODAL REUTILIZABLE PARA CREAR CLIENTES
    
    Para usar este modal en cualquier parte de tu aplicación:
    
    1. Incluye el componente Livewire:
       <livewire:create-customer-modal />
    
    2. Para abrir el modal, dispara el evento desde cualquier botón:
       <button @click="$dispatch('open-create-customer-modal')">
           Crear Cliente
       </button>
    
    3. Para escuchar cuando se crea un cliente, usa:
       <div x-data @customer-created.window="handleCustomerCreated($event.detail)">
           <!-- Tu contenido -->
       </div>
    
    4. El evento 'customer-created' incluye:
       - customerId: ID del cliente creado
       - customer: { id, name, identification }
--}}

<!-- Ejemplo de uso -->
<div>
    <!-- Botón para abrir el modal -->
    <button @click="$dispatch('open-create-customer-modal')" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        Crear Cliente
    </button>
    
    <!-- Incluir el modal -->
    <livewire:create-customer-modal />
    
    <!-- Escuchar cuando se crea un cliente -->
    <div x-data @customer-created.window="console.log('Cliente creado:', $event.detail)">
        <!-- Tu contenido aquí -->
    </div>
</div>

