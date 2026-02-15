@props([
    'stats' => [],
    'currentDate' => null,
])

@php
    $selectedDate = $currentDate instanceof \Carbon\Carbon
        ? $currentDate
        : \Carbon\Carbon::parse($currentDate ?? now());

    $stats = array_merge([
        'rooms_total' => 0,
        'rooms_occupied' => 0,
        'rooms_available' => 0,
        'occupancy_rate' => 0,
        'reservations_active' => 0,
        'arrivals_today' => 0,
        'departures_today' => 0,
        'guests_total' => 0,
        'adults_total' => 0,
        'children_total' => 0,
    ], $stats);
@endphp

<section class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <h2 class="text-sm sm:text-base font-bold text-gray-900">Resumen diario de operacion</h2>
        <span class="text-xs sm:text-sm text-gray-500">
            Fecha: <span class="font-semibold text-gray-700">{{ $selectedDate->format('d/m/Y') }}</span>
        </span>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Habitaciones</p>
            <p class="text-xl font-black text-gray-900 mt-1">{{ number_format((int) $stats['rooms_total'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500 mt-1">
                {{ (int) $stats['rooms_occupied'] }} ocupadas / {{ (int) $stats['rooms_available'] }} libres
            </p>
        </article>

        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Ocupacion</p>
            <p class="text-xl font-black text-gray-900 mt-1">{{ (int) $stats['occupancy_rate'] }}%</p>
            <div class="mt-2 h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full" style="width: {{ max(0, min(100, (int) $stats['occupancy_rate'])) }}%"></div>
            </div>
        </article>

        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Reservas del dia</p>
            <p class="text-xl font-black text-gray-900 mt-1">{{ number_format((int) $stats['reservations_active'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Activas en la fecha seleccionada</p>
        </article>

        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Llegadas</p>
            <p class="text-xl font-black text-emerald-700 mt-1">{{ number_format((int) $stats['arrivals_today'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Check-ins del dia</p>
        </article>

        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Salidas</p>
            <p class="text-xl font-black text-orange-700 mt-1">{{ number_format((int) $stats['departures_today'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500 mt-1">Check-outs del dia</p>
        </article>

        <article class="rounded-xl border border-gray-100 bg-gray-50 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Personas del dia</p>
            <p class="text-xl font-black text-gray-900 mt-1">{{ number_format((int) $stats['guests_total'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500 mt-1">
                Adultos: {{ (int) $stats['adults_total'] }} / Ninos: {{ (int) $stats['children_total'] }}
            </p>
        </article>
    </div>
</section>
