@extends('layouts.app')

@section('title', 'Reservas')
@section('header', 'Gestión de Reservas')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <x-reservations.header :totalReservations="$reservations->total()" />

    @livewire('reservations.reservation-stats')

    <x-reservations.view-switcher :view="$view" :date="$date" />

    @if($view === 'calendar')
    <x-reservations.calendar-legend />

    <x-reservations.calendar-grid :rooms="$rooms" :daysInMonth="$daysInMonth" />
    @endif

    @if($view === 'list')
    <x-reservations.reservations-list :reservations="$reservations" />
    @endif
</div>

<x-reservations.detail-modal />
<x-reservations.delete-modal />
<x-room-manager.room-release-confirmation-modal />
<x-reservations.calendar-scripts />
@endsection

