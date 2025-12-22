@extends('layouts.app')

@section('title', 'Detalle de Venta')
@section('header', 'Detalle de Venta')

@section('content')
<livewire:show-sale :sale="$sale" />
@endsection
