<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Soporte de Reserva #{{ $reservation->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 20px; margin-bottom: 30px; }
        .hotel-name { font-size: 24px; font-bold: true; color: #10b981; margin: 0; }
        .document-type { font-size: 18px; color: #666; margin: 5px 0; }
        .section-title { font-size: 14px; font-weight: bold; background: #f3f4f6; padding: 5px 10px; margin-bottom: 10px; border-left: 4px solid #10b981; }
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-grid td { padding: 8px 10px; vertical-align: top; }
        .label { font-weight: bold; color: #666; width: 30%; }
        .footer { text-align: center; font-size: 10px; color: #999; margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; }
        .amount-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .amount-table th { background: #f3f4f6; text-align: left; padding: 10px; }
        .amount-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="hotel-name">HOTEL SAN PEDRO</h1>
        <p class="document-type">SOPORTE DE RESERVA #{{ $reservation->id }}</p>
        <p style="font-size: 12px; color: #666;">Fecha de emisión: {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">DATOS DEL CLIENTE</div>
    <table class="info-grid">
        <tr>
            <td class="label">Nombre:</td>
            <td>{{ $reservation->customer->name }}</td>
        </tr>
        <tr>
            <td class="label">Correo:</td>
            <td>{{ $reservation->customer->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Teléfono:</td>
            <td>{{ $reservation->customer->phone ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">DETALLES DE LA HABITACIÓN</div>
    <table class="info-grid">
        <tr>
            <td class="label">Habitación:</td>
            <td>{{ $reservation->room->room_number }} ({{ $reservation->room->beds_count }} {{ $reservation->room->beds_count == 1 ? 'Cama' : 'Camas' }})</td>
        </tr>
    </table>

    <div class="section-title">FECHAS DE ESTADÍA</div>
    <table class="info-grid">
        <tr>
            <td class="label">Fecha de Reserva:</td>
            <td>{{ $reservation->reservation_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Check-in:</td>
            <td>{{ $reservation->check_in_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Check-out:</td>
            <td>{{ $reservation->check_out_date->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="section-title">RESUMEN ECONÓMICO</div>
    <table class="amount-table">
        <tr>
            <th>Concepto</th>
            <th style="text-align: right;">Monto</th>
        </tr>
        <tr>
            <td>Valor Total de la Reserva</td>
            <td style="text-align: right;">${{ number_format($reservation->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Abono Realizado</td>
            <td style="text-align: right; color: #10b981;">-${{ number_format($reservation->deposit, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>Saldo Pendiente</td>
            <td style="text-align: right; color: #ef4444;">${{ number_format($reservation->total_amount - $reservation->deposit, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($reservation->notes)
    <div class="section-title" style="margin-top: 20px;">OBSERVACIONES</div>
    <div style="font-size: 12px; padding: 10px; border: 1px solid #eee; border-radius: 5px;">
        {{ $reservation->notes }}
    </div>
    @endif

    <div class="footer">
        <p>Gracias por elegir Hotel San Pedro. Este documento es un soporte de su reserva.</p>
        <p>© {{ date('Y') }} Hotel San Pedro - Todos los derechos reservados</p>
    </div>
</body>
</html>

