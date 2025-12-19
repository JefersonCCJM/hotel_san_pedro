@extends('layouts.app')

@section('title', 'Habitaciones')

@section('header', 'Habitaciones')

@section('content')
    <livewire:room-manager :date="request('date')" :search="request('search')" :status="request('status')" />
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    
    .ts-wrapper.single .ts-control { border-radius: 0.75rem !important; padding: 0.75rem 1.25rem !important; border: 1px solid #e5e7eb !important; background-color: #f9fafb !important; font-size: 0.875rem; font-weight: 700; }
    .ts-dropdown { border-radius: 1rem !important; margin-top: 8px !important; border: 1px solid #f3f4f6 !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important; padding: 0.5rem !important; }
</style>
@endpush
