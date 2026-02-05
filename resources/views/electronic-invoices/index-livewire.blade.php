@extends('layouts.app')

@section('title', 'Facturas Electrónicas')
@section('header', 'Facturas Electrónicas')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Componente Principal -->
    <livewire:electronic-invoices.electronic-invoices-table />
    
    <!-- Modal de Creación -->
    <livewire:electronic-invoices.create-electronic-invoice-modal />
</div>

<script>
    // Manejar tecla ESC para cerrar modales
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Cerrar modales usando Livewire
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('close-create-electronic-invoice-modal');
            }
        }
    });
</script>
@endsection
