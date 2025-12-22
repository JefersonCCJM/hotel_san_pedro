@extends('layouts.app')

@section('title', 'Editar Venta')
@section('header', 'Editar Venta')

@section('content')
<livewire:edit-sale :sale="$sale" />
@endsection
