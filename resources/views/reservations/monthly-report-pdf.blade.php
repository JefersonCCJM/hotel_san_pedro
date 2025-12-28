<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Mensual de Reservaciones - {{ $month->locale('es')->isoFormat('MMMM YYYY') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 16px; margin-bottom: 22px; }
        .hotel-name { font-size: 22px; font-weight: bold; color: #10b981; margin: 0; }
        .document-type { font-size: 15px; color: #666; margin: 6px 0 2px; }
        .report-period { font-size: 12px; color: #111827; font-weight: bold; margin: 0; }
        .meta { font-size: 10px; color: #6b7280; margin-top: 8px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .table th { background: #f3f4f6; text-align: left; padding: 8px; border-bottom: 2px solid #10b981; font-size: 10px; }
        .table td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .table tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; }
        .total-row td { font-weight: bold; background: #f3f4f6; border-top: 2px solid #e5e7eb; }
        .footer { text-align: center; font-size: 9px; color: #9ca3af; margin-top: 30px; border-top: 1px solid #eee; padding-top: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="hotel-name">HOTEL SAN PEDRO</h1>
        <p class="document-type">REPORTE MENSUAL DE RESERVACIONES</p>
        <p class="report-period">{{ $month->locale('es')->isoFormat('MMMM [de] YYYY') }}</p>
        <p class="meta">Fecha de emisión: {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 7%;">ID</th>
                <th style="width: 21%;">Cliente</th>
                <th style="width: 14%;">Habitación</th>
                <th class="text-center" style="width: 10%;">Check-in</th>
                <th class="text-center" style="width: 10%;">Check-out</th>
                <th class="text-center" style="width: 8%;">Huéspedes</th>
                <th class="text-right" style="width: 10%;">Total</th>
                <th class="text-right" style="width: 10%;">Abono</th>
                <th class="text-right" style="width: 10%;">Pendiente</th>
                <th style="width: 10%;">Pago</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
                <tr>
                    <td>#{{ $reservation->id }}</td>
                    <td>{{ $reservation->customer->name ?? 'N/A' }}</td>
                    <td>
                        @if($reservation->rooms && $reservation->rooms->count() > 0)
                            {{ $reservation->rooms->pluck('room_number')->join(', ') }}
                        @else
                            {{ $reservation->room->room_number ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="text-center">{{ $reservation->check_in_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td class="text-center">{{ $reservation->check_out_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td class="text-center">{{ (int) ($reservation->guests_count ?? 0) }}</td>
                    <td class="text-right">${{ number_format((float) $reservation->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format((float) $reservation->deposit, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format(((float) $reservation->total_amount) - ((float) $reservation->deposit), 0, ',', '.') }}</td>
                    <td>{{ $reservation->payment_method ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center muted" style="padding: 18px;">
                        No hay reservaciones para el período seleccionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($reservations->count() > 0)
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="text-right">TOTALES:</td>
                    <td class="text-right">${{ number_format((float) $reservations->sum('total_amount'), 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format((float) $reservations->sum('deposit'), 0, ',', '.') }}</td>
                    <td class="text-right">
                        ${{ number_format((float) $reservations->sum(function ($r) { return ((float) $r->total_amount) - ((float) $r->deposit); }), 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema de gestión Hotel San Pedro.</p>
        <p>© {{ date('Y') }} Hotel San Pedro - Todos los derechos reservados</p>
    </div>
</body>
</html>


