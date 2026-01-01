<div class="space-y-4 sm:space-y-6" wire:poll.5s>
    <!-- Header -->
    <x-product-list.header :productsCount="$products->total()" />

    <!-- Filters -->
    <x-product-list.filters :categories="$categories" />

    <!-- Products Table -->
    <x-product-list.products-table :products="$products" />

    <!-- Delete Confirmation Modal -->
    <x-confirm-delete-modal 
        title="Eliminar Producto"
        message="¿Estás seguro de que deseas eliminar"
        confirmMethod="deleteProduct"
        itemNameAttribute="name" />
</div>
