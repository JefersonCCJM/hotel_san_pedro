@extends('layouts.app')

@section('title', 'Ventas')
@section('header', 'Gestión de Ventas')

@section('content')
<livewire:sales-manager :date="request('date')" />
@endsection
