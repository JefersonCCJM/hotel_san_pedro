@extends('layouts.app')

@section('title', 'Ventas por Habitación')
@section('header', 'Ventas por Habitación')

@section('content')
<livewire:sales-by-room :date="request('date')" />
@endsection
