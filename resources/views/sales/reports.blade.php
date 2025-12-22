@extends('layouts.app')

@section('title', 'Reportes de Ventas')
@section('header', 'Reportes de Ventas')

@section('content')
<livewire:sales-reports :date="request('date')" />
@endsection
