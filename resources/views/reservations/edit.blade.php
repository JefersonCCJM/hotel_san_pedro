@extends('layouts.app')

@section('title', 'Editar Reserva')
@section('header', 'Editar Reserva')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    @livewire('reservations.reservation-edit', [
        'reservation' => $reservation,
        'rooms' => $rooms,
        'roomsData' => $roomsData,
        'customers' => $customers,
        'identificationDocuments' => $identificationDocuments ?? [],
        'legalOrganizations' => $legalOrganizations ?? [],
        'tributes' => $tributes ?? [],
        'municipalities' => $municipalities ?? [],
    ])
</div>
@endsection


