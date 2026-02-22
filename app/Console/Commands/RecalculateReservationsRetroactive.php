<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationRetroactiveRecalculationService;
use App\Support\HotelTime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecalculateReservationsRetroactive extends Command
{
    protected $signature = 'reservations:recalculate-retroactive
        {--from= : Fecha inicial (YYYY-MM-DD) para filtrar reservas por rango}
        {--to= : Fecha final (YYYY-MM-DD) para filtrar reservas por rango}
        {--reservation-id=* : IDs de reserva especificos}
        {--chunk=200 : Tamano de lote}
        {--dry-run : Simula sin guardar cambios}
        {--force : Permite ejecucion en produccion}
        {--keep-total : No actualizar reservations.total_amount}
        {--without-sales-debt : Excluye deuda de ventas del balance}
        {--strict-paid-outside-range : Falla si hay noches pagadas fuera de rango}
        {--with-trashed : Incluye reservas eliminadas logicamente}';

    protected $description = 'Recalculo retroactivo masivo de stays, stay_nights y finanzas de reservas.';

    public function handle(ReservationRetroactiveRecalculationService $service): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En produccion debes confirmar con --force.');
            $this->line('Sugerido primero: php artisan reservations:recalculate-retroactive --dry-run --force');
            return Command::FAILURE;
        }

        try {
            $from = $this->parseDateOption('from');
            $to = $this->parseDateOption('to');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($from && $to && $from->gt($to)) {
            $this->error('El rango es invalido: --from no puede ser mayor a --to.');
            return Command::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $withTrashed = (bool) $this->option('with-trashed');
        $reservationIds = $this->parseReservationIds((array) $this->option('reservation-id'));

        $baseQuery = Reservation::query()->select('id')->orderBy('id');
        if ($withTrashed) {
            $baseQuery->withTrashed();
        }

        $baseQuery->whereHas('reservationRooms');

        if (!empty($reservationIds)) {
            $baseQuery->whereIn('id', $reservationIds);
        } elseif ($from || $to) {
            $baseQuery->whereHas('reservationRooms', function ($query) use ($from, $to): void {
                if ($from) {
                    $query->whereDate('check_out_date', '>=', $from->toDateString());
                }
                if ($to) {
                    $query->whereDate('check_in_date', '<=', $to->toDateString());
                }
            });
        }

        $totalReservations = (clone $baseQuery)->count();
        if ($totalReservations <= 0) {
            $this->warn('No hay reservas para recalcular con los filtros enviados.');
            return Command::SUCCESS;
        }

        $this->line('Modo: ' . ($dryRun ? 'DRY-RUN (sin persistencia)' : 'EJECUCION REAL'));
        $this->line('Reservas objetivo: ' . $totalReservations);
        $this->line('Chunk: ' . $chunkSize);
        if ($from || $to) {
            $this->line(
                'Rango: '
                . ($from ? $from->toDateString() : '...') . ' -> '
                . ($to ? $to->toDateString() : '...')
            );
        }

        $summary = [
            'processed' => 0,
            'changed' => 0,
            'created_stays' => 0,
            'updated_stays' => 0,
            'created_nights' => 0,
            'updated_nights' => 0,
            'deleted_nights' => 0,
            'paid_flags_updated' => 0,
            'preserved_paid_nights' => 0,
            'updated_reservations' => 0,
            'errors' => 0,
        ];
        $errors = [];

        $options = [
            'dry_run' => $dryRun,
            'keep_total' => (bool) $this->option('keep-total'),
            'without_sales_debt' => (bool) $this->option('without-sales-debt'),
            'strict_paid_outside_range' => (bool) $this->option('strict-paid-outside-range'),
            'operational_date' => HotelTime::currentOperationalDate(),
        ];

        $progress = $this->output->createProgressBar($totalReservations);
        $progress->start();

        $baseQuery->chunkById($chunkSize, function ($rows) use (
            $service,
            $withTrashed,
            $options,
            &$summary,
            &$errors,
            $progress
        ): void {
            $ids = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();

            $reservationsQuery = Reservation::query()
                ->with(['reservationRooms', 'stays', 'sales'])
                ->whereIn('id', $ids);

            if ($withTrashed) {
                $reservationsQuery->withTrashed();
            }

            $reservations = $reservationsQuery->get()->keyBy('id');

            foreach ($ids as $id) {
                $reservation = $reservations->get($id);
                if (!$reservation) {
                    $summary['processed']++;
                    $summary['errors']++;
                    $errors[] = "#{$id}: no encontrada en carga de lote.";
                    $progress->advance();
                    continue;
                }

                try {
                    $result = $service->recalculateReservation($reservation, $options);

                    $summary['processed']++;
                    $summary['changed'] += !empty($result['changed']) ? 1 : 0;
                    $summary['created_stays'] += (int) ($result['created_stays'] ?? 0);
                    $summary['updated_stays'] += (int) ($result['updated_stays'] ?? 0);
                    $summary['created_nights'] += (int) ($result['created_nights'] ?? 0);
                    $summary['updated_nights'] += (int) ($result['updated_nights'] ?? 0);
                    $summary['deleted_nights'] += (int) ($result['deleted_nights'] ?? 0);
                    $summary['paid_flags_updated'] += (int) ($result['paid_flags_updated'] ?? 0);
                    $summary['preserved_paid_nights'] += (int) ($result['preserved_paid_nights'] ?? 0);
                    $summary['updated_reservations'] += !empty($result['updated_reservation_row']) ? 1 : 0;
                } catch (Throwable $e) {
                    $summary['processed']++;
                    $summary['errors']++;

                    $message = "#{$id}: {$e->getMessage()}";
                    $errors[] = $message;

                    Log::error('Error en recalc retroactivo de reserva', [
                        'reservation_id' => $id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                } finally {
                    $progress->advance();
                }
            }
        });

        $progress->finish();
        $this->newLine(2);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Procesadas', (string) $summary['processed']],
                ['Con cambios', (string) $summary['changed']],
                ['Stays creadas', (string) $summary['created_stays']],
                ['Stays actualizadas', (string) $summary['updated_stays']],
                ['Noches creadas', (string) $summary['created_nights']],
                ['Noches actualizadas', (string) $summary['updated_nights']],
                ['Noches eliminadas', (string) $summary['deleted_nights']],
                ['Flags is_paid actualizados', (string) $summary['paid_flags_updated']],
                ['Noches pagadas preservadas', (string) $summary['preserved_paid_nights']],
                ['Reservas actualizadas', (string) $summary['updated_reservations']],
                ['Errores', (string) $summary['errors']],
            ]
        );

        if (!empty($errors)) {
            $this->warn('Primeros errores encontrados (max 20):');
            foreach (array_slice($errors, 0, 20) as $errorLine) {
                $this->line(' - ' . $errorLine);
            }
        }

        if ($summary['errors'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int,mixed> $values
     * @return array<int,int>
     */
    private function parseReservationIds(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function parseDateOption(string $option): ?Carbon
    {
        $value = trim((string) $this->option($option));
        if ($value === '') {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value, HotelTime::timezone());
        if (!$date) {
            throw new \InvalidArgumentException("Formato invalido en --{$option}. Usa YYYY-MM-DD.");
        }

        return $date->startOfDay();
    }
}

