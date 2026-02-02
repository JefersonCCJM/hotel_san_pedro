<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\Product;
use App\Enums\RoomStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ReportService
{
    /**
     * Generate generic report by entity type.
     */
    public function generateReport(
        string $entityType,
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy = null,
        ?array $filters = null
    ): array {
        return match ($entityType) {
            'sales' => $this->getSalesReport($startDate, $endDate, $groupBy, $filters),
            'rooms' => $this->getRoomsReport($startDate, $endDate, $groupBy, $filters),
            'receptionists' => $this->getReceptionistsReport($startDate, $endDate, $groupBy, $filters),
            'reservations' => $this->getReservationsReport($startDate, $endDate, $groupBy, $filters),
            'cleaning' => $this->getCleaningReport($startDate, $endDate, $groupBy, $filters),
            'customers' => $this->getCustomersReport($startDate, $endDate, $groupBy, $filters),
            'products' => $this->getProductsReport($startDate, $endDate, $groupBy, $filters),
            'electronic_invoices' => $this->getElectronicInvoicesReport($startDate, $endDate, $groupBy, $filters),
            default => throw new \InvalidArgumentException("Tipo de entidad desconocido: {$entityType}"),
        };
    }

    /**
     * Get sales report with flexible grouping.
     */
    private function getSalesReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = Sale::with(['user', 'room', 'items.product'])
            ->whereBetween('sale_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Apply filters
        if ($filters) {
            if (isset($filters['receptionist_id']) && $filters['receptionist_id'] !== '') {
                $query->where('user_id', $filters['receptionist_id']);
            }
            if (isset($filters['room_id']) && $filters['room_id'] !== '') {
                if ($filters['room_id'] === 'none') {
                    $query->whereNull('room_id');
                } else {
                    $query->where('room_id', $filters['room_id']);
                }
            }
            if (isset($filters['payment_method']) && $filters['payment_method'] !== '') {
                $query->where('payment_method', $filters['payment_method']);
            }
            if (isset($filters['debt_status']) && $filters['debt_status'] !== '') {
                $query->where('debt_status', $filters['debt_status']);
            }
            if (isset($filters['min_amount']) && $filters['min_amount'] !== null) {
                $query->where('total', '>=', $filters['min_amount']);
            }
            if (isset($filters['max_amount']) && $filters['max_amount'] !== null) {
                $query->where('total', '<=', $filters['max_amount']);
            }
        }

        $sales = $query->get();

        // Separate sales by type: room sales vs normal sales
        $roomSales = $sales->whereNotNull('room_id');
        $individualSales = $sales->whereNull('room_id');

        $summary = [
            'total_sales' => $sales->sum('total'),
            'total_cash' => $sales->sum('cash_amount') ?? 0,
            'total_transfer' => $sales->sum('transfer_amount') ?? 0,
            'total_debt' => $sales->where('debt_status', 'pendiente')->sum('total'),
            'total_count' => $sales->count(),
            'room_sales_count' => $roomSales->count(),
            'room_sales_total' => $roomSales->sum('total'),
            'individual_sales_count' => $individualSales->count(),
            'individual_sales_total' => $individualSales->sum('total'),
            'by_payment_method' => [
                'efectivo' => $sales->where('payment_method', 'efectivo')->sum('total'),
                'transferencia' => $sales->where('payment_method', 'transferencia')->sum('total'),
                'ambos' => $sales->where('payment_method', 'ambos')->sum('total'),
                'pendiente' => $sales->where('payment_method', 'pendiente')->sum('total'),
            ],
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupSalesBy($sales, $groupBy);
        }

        return [
            'entity_type' => 'sales',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $sales,
            'detailed_data' => $sales,
        ];
    }

    /**
     * Group sales by different criteria.
     */
    private function groupSalesBy(Collection $sales, string $groupBy): array
    {
        return match ($groupBy) {
            'receptionist' => $sales->groupBy('user_id')->map(function (Collection $group): array {
                $user = $group->first()->user;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total' => $group->sum('total'),
                    'count' => $group->count(),
                    'cash' => $group->sum('cash_amount') ?? 0,
                    'transfer' => $group->sum('transfer_amount') ?? 0,
                ];
            })->values()->toArray(),
            'room' => $sales->whereNotNull('room_id')->groupBy('room_id')->map(function (Collection $group): array {
                $room = $group->first()->room;
                return [
                    'id' => $room->id,
                    'name' => $room->room_number,
                    'total' => $group->sum('total'),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            'payment_method' => $sales->groupBy('payment_method')->map(function (Collection $group): array {
                return [
                    'name' => $group->keys()->first(),
                    'total' => $group->sum('total'),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            'date' => $sales->groupBy(function (Sale $sale): string {
                return $sale->sale_date->format('Y-m-d');
            })->map(function (Collection $group): array {
                return [
                    'date' => $group->keys()->first(),
                    'total' => $group->sum('total'),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Get rooms report with flexible grouping.
     */
    private function getRoomsReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = Room::with(['reservations' => function ($q) use ($startDate, $endDate): void {
            $q->where(function($q2) use ($startDate, $endDate) {
                $q2->whereBetween('check_in_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                   ->orWhereBetween('check_out_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                   ->orWhere(function($q3) use ($startDate, $endDate) {
                       $q3->where('check_in_date', '<=', $startDate->format('Y-m-d'))
                          ->where('check_out_date', '>=', $endDate->format('Y-m-d'));
                   });
            })->with(['customer', 'sales.product']);
        }]);

        // Apply filters
        if ($filters) {
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['room_id'])) {
                $query->where('id', $filters['room_id']);
            }
        }

        $rooms = $query->get();

        // Enrich rooms with current reservation info for each date in range
        $detailedRooms = $rooms->map(function (Room $room) use ($startDate, $endDate): array {
            $roomData = [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status->value,
                'status_label' => $room->status->label(),
                'reservations_history' => [],
            ];

            // Get reservations for each day in the range
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $reservationForDate = $room->reservations->first(function ($res) use ($currentDate) {
                    $checkIn = Carbon::parse($res->check_in_date);
                    $checkOut = Carbon::parse($res->check_out_date);
                    return $currentDate->between($checkIn, $checkOut->copy()->subDay()) || 
                           ($currentDate->isSameDay($checkIn) && !$currentDate->isSameDay($checkOut));
                });

                if ($reservationForDate) {
                    $customer = $reservationForDate->customer;
                    $sales = $reservationForDate->sales;
                    
                    $roomData['reservations_history'][] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'reservation' => [
                            'id' => $reservationForDate->id,
                            'customer_id' => $reservationForDate->customer_id,
                            'customer_name' => $customer->name ?? 'N/A',
                            'customer_identification' => $customer->identification ?? 'N/A',
                            'customer_phone' => $customer->phone ?? 'N/A',
                            'customer_email' => $customer->email ?? 'N/A',
                            'guests_count' => $reservationForDate->guests_count ?? 1,
                            'reservation_date' => $reservationForDate->reservation_date->format('Y-m-d'),
                            'check_in_date' => $reservationForDate->check_in_date->format('Y-m-d'),
                            'check_out_date' => $reservationForDate->check_out_date->format('Y-m-d'),
                            'total_amount' => (float)$reservationForDate->total_amount,
                            'deposit' => (float)$reservationForDate->deposit,
                            'payment_method' => $reservationForDate->payment_method ?? 'N/A',
                            'pending_amount' => (float)$reservationForDate->total_amount - (float)$reservationForDate->deposit,
                            'notes' => $reservationForDate->notes ?? '',
                            'sales' => $sales->map(function ($sale) {
                                return [
                                    'id' => $sale->id,
                                    'product_name' => $sale->product->name ?? 'N/A',
                                    'product_id' => $sale->product_id,
                                    'quantity' => $sale->quantity,
                                    'unit_price' => (float)$sale->unit_price,
                                    'total' => (float)$sale->total,
                                    'payment_method' => $sale->payment_method ?? 'N/A',
                                    'is_paid' => $sale->is_paid ?? false,
                                    'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                                ];
                            })->toArray(),
                            'sales_count' => $sales->count(),
                            'sales_total' => (float)$sales->sum('total'),
                            'sales_paid' => (float)$sales->where('is_paid', true)->sum('total'),
                            'sales_pending' => (float)$sales->where('is_paid', false)->sum('total'),
                        ],
                    ];
                }
                $currentDate->addDay();
            }

            return $roomData;
        });

        // TODO: Refactorizar para usar la nueva arquitectura sin status persistido
        // Por ahora, mostrar solo habitaciones activas como disponibles
        $summary = [
            'total_rooms' => $rooms->count(),
            'occupied_rooms' => $rooms->filter(fn($room) => $room->isOccupied())->count(),
            'available_rooms' => $rooms->filter(fn($room) => !$room->isOccupied() && $room->is_active)->count(),
            'cleaning_rooms' => $rooms->filter(fn($room) => $room->cleaningStatus()['code'] === 'pendiente')->count(),
            'maintenance_rooms' => $rooms->where('is_active', false)->count(),
        ];

        $totalRevenue = $rooms->sum(function (Room $room): float {
            return $room->reservations->sum('total_amount');
        });

        $summary['total_revenue'] = $totalRevenue;

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupRoomsBy($rooms, $groupBy);
        }

        return [
            'entity_type' => 'rooms',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $rooms,
            'detailed_data' => $detailedRooms->toArray(),
        ];
    }

    /**
     * Group rooms by different criteria.
     */
    private function groupRoomsBy(Collection $rooms, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $rooms->groupBy('status')->map(function (Collection $group): array {
                $status = $group->first()->status;
                return [
                    'name' => $status->label(),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            'room' => $rooms->map(function (Room $room): array {
                return [
                    'id' => $room->id,
                    'name' => $room->room_number,
                    'status' => $room->status->label(),
                    'reservations_count' => $room->reservations->count(),
                    'revenue' => $room->reservations->sum('total_amount'),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Get receptionists report.
     */
    private function getReceptionistsReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $receptionists = User::role(['Recepcionista Día', 'Recepcionista Noche'])->get();

        $reportData = $receptionists->map(function (User $user) use ($startDate, $endDate): array {
            $sales = Sale::where('user_id', $user->id)
                ->whereBetween('sale_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'total_sales' => $sales->sum('total'),
                'sales_count' => $sales->count(),
                'cash' => $sales->sum('cash_amount') ?? 0,
                'transfer' => $sales->sum('transfer_amount') ?? 0,
            ];
        });

        $summary = [
            'total_receptionists' => $receptionists->count(),
            'total_sales' => $reportData->sum('total_sales'),
            'total_sales_count' => $reportData->sum('sales_count'),
        ];

        return [
            'entity_type' => 'receptionists',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $reportData->values()->toArray(),
            'data' => $receptionists,
            'detailed_data' => $reportData->values()->toArray(),
        ];
    }

    /**
     * Get reservations report.
     */
    private function getReservationsReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = Reservation::with(['customer', 'room'])
            ->whereBetween('check_in_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orWhereBetween('check_out_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Apply filters
        if ($filters) {
            if (isset($filters['room_id'])) {
                $query->where('room_id', $filters['room_id']);
            }
            if (isset($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }
            if (isset($filters['payment_status'])) {
                match($filters['payment_status']) {
                    'paid' => $query->whereColumn('deposit', '>=', 'total_amount'),
                    'partially_paid' => $query->whereColumn('deposit', '<', 'total_amount')->where('deposit', '>', 0),
                    'unpaid' => $query->where('deposit', 0),
                    default => null,
                };
            }
            if (isset($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }
        }

        $reservations = $query->get();

        $summary = [
            'total_reservations' => $reservations->count(),
            'total_amount' => $reservations->sum('total_amount'),
            'total_deposit' => $reservations->sum('deposit'),
            'total_pending' => $reservations->sum('total_amount') - $reservations->sum('deposit'),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupReservationsBy($reservations, $groupBy);
        }

        // Create detailed data for reservations
        $detailedData = $reservations->map(function (Reservation $reservation): array {
            return [
                'id' => $reservation->id,
                'customer_name' => $reservation->customer->name ?? 'N/A',
                'customer_phone' => $reservation->customer->phone ?? 'N/A',
                'room_number' => $reservation->room->room_number ?? 'N/A',
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'guests_count' => $reservation->guests_count ?? 1,
                'total_amount' => (float)$reservation->total_amount,
                'deposit' => (float)$reservation->deposit,
                'pending_amount' => (float)$reservation->total_amount - (float)$reservation->deposit,
                'payment_method' => $reservation->payment_method ?? 'N/A',
                'payment_status' => $reservation->deposit >= $reservation->total_amount ? 'paid' : 
                                   ($reservation->deposit > 0 ? 'partially_paid' : 'unpaid'),
            ];
        })->values()->toArray();

        return [
            'entity_type' => 'reservations',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $reservations,
            'detailed_data' => $detailedData,
        ];
    }

    /**
     * Group reservations by different criteria.
     */
    private function groupReservationsBy(Collection $reservations, string $groupBy): array
    {
        return match ($groupBy) {
            'room' => $reservations->groupBy('room_id')->map(function (Collection $group): array {
                $room = $group->first()->room;
                return [
                    'id' => $room->id,
                    'name' => $room->room_number,
                    'count' => $group->count(),
                    'total_amount' => $group->sum('total_amount'),
                    'total_deposit' => $group->sum('deposit'),
                ];
            })->values()->toArray(),
            'customer' => $reservations->groupBy('customer_id')->map(function (Collection $group): array {
                $customer = $group->first()->customer;
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'count' => $group->count(),
                    'total_amount' => $group->sum('total_amount'),
                ];
            })->values()->toArray(),
            'date' => $reservations->groupBy(function (Reservation $reservation): string {
                return $reservation->check_in_date->format('Y-m-d');
            })->map(function (Collection $group): array {
                return [
                    'date' => $group->keys()->first(),
                    'count' => $group->count(),
                    'total_amount' => $group->sum('total_amount'),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Get cleaning report (rooms that need cleaning).
     */
    private function getCleaningReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = Room::with(['reservations.customer', 'reservations.sales.product'])
            ->whereIn('status', [RoomStatus::LIMPIEZA, RoomStatus::SUCIA]);

        // Apply filters
        if ($filters && isset($filters['room_id'])) {
            $query->where('id', $filters['room_id']);
        }

        $rooms = $query->get();

        // Create detailed data for cleaning report
        $detailedData = $rooms->map(function (Room $room): array {
            $currentReservation = $room->reservations()
                ->where('check_in_date', '<=', now())
                ->where('check_out_date', '>=', now())
                ->first();
            
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status->value,
                'status_label' => $room->status->label(),
                'current_customer' => $currentReservation?->customer?->name ?? 'Disponible',
                'check_in_date' => $currentReservation?->check_in_date?->format('Y-m-d') ?? '-',
                'check_out_date' => $currentReservation?->check_out_date?->format('Y-m-d') ?? '-',
                'total_amount' => $currentReservation ? (float)$currentReservation->total_amount : 0,
                'deposit' => $currentReservation ? (float)$currentReservation->deposit : 0,
                'pending_amount' => $currentReservation ? (float)$currentReservation->total_amount - (float)$currentReservation->deposit : 0,
            ];
        })->values()->toArray();

        $summary = [
            'total_cleaning' => $rooms->count(),
            'limpieza' => $rooms->where('status', RoomStatus::LIMPIEZA)->count(),
            'sucia' => $rooms->where('status', RoomStatus::SUCIA)->count(),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupCleaningBy($rooms, $groupBy);
        }

        return [
            'entity_type' => 'cleaning',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $rooms,
            'detailed_data' => $detailedData,
        ];
    }

    /**
     * Group cleaning by different criteria.
     */
    private function groupCleaningBy(Collection $rooms, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $rooms->groupBy('status')->map(function (Collection $group): array {
                $status = $group->first()->status;
                return [
                    'name' => $status->label(),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            'room' => $rooms->map(function (Room $room): array {
                return [
                    'id' => $room->id,
                    'name' => $room->room_number,
                    'status' => $room->status->label(),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Get available grouping options for an entity type.
     */
    public function getGroupingOptions(string $entityType): array
    {
        return match ($entityType) {
            'sales' => ['receptionist', 'room', 'payment_method', 'date'],
            'rooms' => ['status', 'room'],
            'reservations' => ['room', 'customer', 'date'],
            'cleaning' => ['status', 'room'],
            'receptionists' => [],
            'customers' => ['status', 'electronic_invoice'],
            'products' => ['category', 'status'],
            'electronic_invoices' => ['status', 'customer', 'document_type', 'date'],
            default => [],
        };
    }

    /**
     * Get available filter options for an entity type.
     */
    public function getFilterOptions(string $entityType): array
    {
        return match ($entityType) {
            'sales' => ['receptionist_id', 'room_id', 'payment_method', 'debt_status'],
            'rooms' => ['status', 'room_id'],
            'reservations' => ['room_id', 'customer_id', 'payment_status', 'payment_method'],
            'cleaning' => ['room_id'],
            'receptionists' => [],
            'customers' => ['is_active', 'requires_electronic_invoice'],
            'products' => ['group', 'category_id', 'status', 'low_stock'],
            'electronic_invoices' => ['status', 'customer_id', 'document_type_id'],
            default => [],
        };
    }

    /**
     * Translate entity type to Spanish.
     */
    public function translateEntityType(string $entityType): string
    {
        return match ($entityType) {
            'sales' => 'Ventas',
            'rooms' => 'Habitaciones',
            'receptionists' => 'Recepcionistas',
            'reservations' => 'Reservas',
            'cleaning' => 'Limpieza',
            'customers' => 'Clientes',
            'products' => 'Productos',
            'electronic_invoices' => 'Facturas Electrónicas',
            default => ucfirst(str_replace('_', ' ', $entityType)),
        };
    }

    /**
     * Translate grouping option to Spanish.
     */
    public function translateGroupingOption(string $option): string
    {
        return match ($option) {
            'receptionist' => 'Recepcionista',
            'room' => 'Habitación',
            'payment_method' => 'Método de Pago',
            'date' => 'Fecha',
            'status' => 'Estado',
            'customer' => 'Cliente',
            'electronic_invoice' => 'Facturación Electrónica',
            'category' => 'Categoría',
            'document_type' => 'Tipo de Documento',
            default => ucfirst(str_replace('_', ' ', $option)),
        };
    }

    /**
     * Translate summary key to Spanish.
     */
    public function translateSummaryKey(string $key): string
    {
        return match ($key) {
            'total_sales' => 'Total Ventas',
            'total_cash' => 'Total Efectivo',
            'total_transfer' => 'Total Transferencia',
            'total_debt' => 'Total Deuda',
            'total_count' => 'Total',
            'total_rooms' => 'Total Habitaciones',
            'occupied_rooms' => 'Habitaciones Ocupadas',
            'available_rooms' => 'Habitaciones Disponibles',
            'cleaning_rooms' => 'Habitaciones en Limpieza',
            'maintenance_rooms' => 'Habitaciones en Mantenimiento',
            'room_sales_count' => 'Ventas en Habitaciones',
            'room_sales_total' => 'Total Ventas Habitaciones',
            'individual_sales_count' => 'Ventas Normales',
            'individual_sales_total' => 'Total Ventas Normales',
            'by_payment_method' => 'Por Método de Pago',
            'total_revenue' => 'Ingresos Totales',
            'total_receptionists' => 'Total Recepcionistas',
            'total_sales_count' => 'Total Ventas',
            'total_reservations' => 'Total Reservas',
            'total_amount' => 'Monto Total',
            'total_deposit' => 'Total Depósito',
            'total_pending' => 'Total Pendiente',
            'total_cleaning' => 'Total Limpieza',
            'limpieza' => 'En Limpieza',
            'sucia' => 'Sucia',
            'total_customers' => 'Total Clientes',
            'active_customers' => 'Clientes Activos',
            'inactive_customers' => 'Clientes Inactivos',
            'total_products' => 'Total Productos',
            'low_stock_products' => 'Productos Bajo Stock',
            'total_categories' => 'Total Categorías',
            'total_invoices' => 'Total Facturas',
            'pending_invoices' => 'Facturas Pendientes',
            'validated_invoices' => 'Facturas Validadas',
            'total_suppliers' => 'Total Proveedores',
            'sales_products' => 'Productos de Venta',
            'aseo_products' => 'Insumos de Aseo',
            'active_products' => 'Productos Activos',
            'out_of_stock' => 'Sin Stock',
            'total_value' => 'Valor Total Inventario',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    /**
     * Get customers report.
     */
    private function getCustomersReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = \App\Models\Customer::with(['taxProfile']);

        // Apply date filter based on creation or reservation dates
        if ($filters && isset($filters['date_type']) && $filters['date_type'] === 'reservation') {
            $query->whereHas('reservations', function($q) use ($startDate, $endDate) {
                $q->whereBetween('reservation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            });
        } else {
            $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        }

        // Apply filters
        if ($filters) {
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $query->where('is_active', $filters['is_active'] === 'true');
            }
            if (isset($filters['requires_electronic_invoice']) && $filters['requires_electronic_invoice'] !== '') {
                $query->where('requires_electronic_invoice', $filters['requires_electronic_invoice'] === 'true');
            }
        }

        $customers = $query->get();

        // Load reservations count and sum for each customer
        $customers->loadCount(['reservations' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('reservation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        }]);
        $customers->loadSum(['reservations' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('reservation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        }], 'total_amount');

        $summary = [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('is_active', true)->count(),
            'inactive_customers' => $customers->where('is_active', false)->count(),
            'with_electronic_invoice' => $customers->where('requires_electronic_invoice', true)->count(),
            'total_reservations' => $customers->sum('reservations_count'),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupCustomersBy($customers, $groupBy);
        }

        // Detailed data for charts/tables
        $detailedData = $customers->map(function ($customer): array {
            return [
                'id' => $customer->id,
                'name' => $customer->name ?? 'N/A',
                'status' => $customer->is_active ? 'activo' : 'inactivo',
                'requires_electronic_invoice' => $customer->requires_electronic_invoice ? 'si' : 'no',
                'reservations_count' => (int) ($customer->reservations_count ?? 0),
                'total' => (float) ($customer->reservations_sum_total_amount ?? 0),
                'total_reservations' => (int) ($customer->reservations_count ?? 0),
            ];
        })->values()->toArray();

        return [
            'entity_type' => 'customers',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $customers,
            'detailed_data' => $detailedData,
        ];
    }

    /**
     * Get products report.
     */
    private function getProductsReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = \App\Models\Product::with(['category']);

        // Apply date filter based on creation
        $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        $aseoKeywords = ['aseo', 'limpieza', 'amenities', 'insumo', 'papel', 'jabon', 'cloro', 'mantenimiento'];

        // Apply filters
        if ($filters) {
            if (isset($filters['group']) && $filters['group'] !== '') {
                if ($filters['group'] === 'aseo') {
                    $query->whereHas('category', function($q) use ($aseoKeywords) {
                        $q->where(function($sub) use ($aseoKeywords) {
                            foreach ($aseoKeywords as $kw) {
                                $sub->orWhere('name', 'like', "%{$kw}%");
                            }
                        });
                    });
                } else {
                    $query->whereHas('category', function($q) use ($aseoKeywords) {
                        foreach ($aseoKeywords as $kw) {
                            $q->where('name', 'not like', "%{$kw}%");
                        }
                    });
                }
            }
            if (isset($filters['category_id']) && $filters['category_id'] !== '') {
                $query->where('category_id', $filters['category_id']);
            }
            if (isset($filters['status']) && $filters['status'] !== '') {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['low_stock']) && $filters['low_stock'] === 'true') {
                $query->whereColumn('quantity', '<=', 'low_stock_threshold');
            }
        }

        $products = $query->get();

        $summary = [
            'total_products' => $products->count(),
            'active_products' => $products->where('status', 'active')->count(),
            'low_stock_products' => $products->filter(fn($p) => $p->hasLowStock())->count(),
            'out_of_stock' => $products->where('quantity', 0)->count(),
            'total_value' => $products->sum(fn($p) => $p->quantity * $p->price),
            'sales_products' => $products->filter(function($p) use ($aseoKeywords) {
                if (!$p->category) return true;
                $catName = strtolower($p->category->name);
                foreach ($aseoKeywords as $kw) if (str_contains($catName, $kw)) return false;
                return true;
            })->count(),
            'aseo_products' => $products->filter(function($p) use ($aseoKeywords) {
                if (!$p->category) return false;
                $catName = strtolower($p->category->name);
                foreach ($aseoKeywords as $kw) if (str_contains($catName, $kw)) return true;
                return false;
            })->count(),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupProductsBy($products, $groupBy);
        }

        // Detailed data for charts/tables
        $detailedData = $products->map(function ($product) use ($aseoKeywords): array {
            $isAseo = false;
            if ($product->category) {
                $catName = strtolower($product->category->name);
                foreach ($aseoKeywords as $kw) if (str_contains($catName, $kw)) $isAseo = true;
            }

            return [
                'id' => $product->id,
                'name' => $product->name ?? 'N/A',
                'category' => $product->category->name ?? 'N/A',
                'group' => $isAseo ? 'Insumos de Aseo' : 'Productos de Venta',
                'status' => $product->status ?? 'N/A',
                'quantity' => (int) ($product->quantity ?? 0),
                'price' => (float) ($product->price ?? 0),
                'value_total' => (float) (($product->quantity ?? 0) * ($product->price ?? 0)),
            ];
        })->values()->toArray();

        return [
            'entity_type' => 'products',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $products,
            'detailed_data' => $detailedData,
        ];
    }

    /**
     * Get categories report.
     */
    private function getCategoriesReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = \App\Models\Category::withCount('products');

        // Apply date filter
        $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Apply filters
        if ($filters) {
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $query->where('is_active', $filters['is_active'] === 'true');
            }
        }

        $categories = $query->get();

        $summary = [
            'total_categories' => $categories->count(),
            'active_categories' => $categories->where('is_active', true)->count(),
            'total_products' => $categories->sum('products_count'),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupCategoriesBy($categories, $groupBy);
        }

        return [
            'entity_type' => 'categories',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $categories,
        ];
    }

    /**
     * Get electronic invoices report.
     */
    private function getElectronicInvoicesReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = \App\Models\ElectronicInvoice::with(['customer', 'documentType', 'operationType']);

        // Apply date filter based on creation
        $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Apply filters
        if ($filters) {
            if (isset($filters['status']) && $filters['status'] !== '') {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['customer_id']) && $filters['customer_id'] !== '') {
                $query->where('customer_id', $filters['customer_id']);
            }
            if (isset($filters['document_type_id']) && $filters['document_type_id'] !== '') {
                $query->where('document_type_id', $filters['document_type_id']);
            }
        }

        $invoices = $query->get();

        $summary = [
            'total_invoices' => $invoices->count(),
            'pending_invoices' => $invoices->where('status', 'pending')->count(),
            'validated_invoices' => $invoices->where('status', 'validated')->count(),
            'total_amount' => $invoices->sum('total'),
            'total_tax' => $invoices->sum('tax_amount'),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupElectronicInvoicesBy($invoices, $groupBy);
        }

        // Create detailed data for electronic invoices
        $detailedData = $invoices->map(function ($invoice): array {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->document ?? 'N/A',
                'customer_name' => $invoice->customer->name ?? 'N/A',
                'document_type' => $invoice->documentType->name ?? 'N/A',
                'status' => $invoice->status ?? 'N/A',
                'total' => (float)$invoice->total,
                'tax_amount' => (float)$invoice->tax_amount,
                'cufe' => $invoice->cufe ?? 'N/A',
                'created_at' => $invoice->created_at->format('Y-m-d'),
            ];
        })->values()->toArray();

        return [
            'entity_type' => 'electronic_invoices',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $invoices,
            'detailed_data' => $detailedData,
        ];
    }

    /**
     * Get suppliers report.
     */
    private function getSuppliersReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $groupBy,
        ?array $filters
    ): array {
        $query = \App\Models\Supplier::withCount('products');

        // Apply date filter
        $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Apply filters
        if ($filters) {
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $query->where('is_active', $filters['is_active'] === 'true');
            }
        }

        $suppliers = $query->get();

        $summary = [
            'total_suppliers' => $suppliers->count(),
            'active_suppliers' => $suppliers->where('is_active', true)->count(),
            'total_products' => $suppliers->sum('products_count'),
        ];

        $grouped = [];
        if ($groupBy) {
            $grouped = $this->groupSuppliersBy($suppliers, $groupBy);
        }

        return [
            'entity_type' => 'suppliers',
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'summary' => $summary,
            'grouped' => $grouped,
            'data' => $suppliers,
        ];
    }

    /**
     * Group customers by different criteria.
     */
    private function groupCustomersBy(Collection $customers, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $customers->groupBy('is_active')->map(function (Collection $group): array {
                return [
                    'name' => $group->first()->is_active ? 'Activos' : 'Inactivos',
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            'electronic_invoice' => $customers->groupBy('requires_electronic_invoice')->map(function (Collection $group): array {
                return [
                    'name' => $group->first()->requires_electronic_invoice ? 'Con Facturación Electrónica' : 'Sin Facturación Electrónica',
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Group products by different criteria.
     */
    private function groupProductsBy(Collection $products, string $groupBy): array
    {
        return match ($groupBy) {
            'category' => $products->groupBy('category_id')->map(function (Collection $group): array {
                $category = $group->first()->category;
                return [
                    'name' => $category ? $category->name : 'Sin Categoría',
                    'count' => $group->count(),
                    'total_value' => $group->sum(fn($p) => $p->quantity * $p->price),
                ];
            })->values()->toArray(),
            'status' => $products->groupBy('status')->map(function (Collection $group): array {
                return [
                    'name' => ucfirst($group->keys()->first()),
                    'count' => $group->count(),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Group categories by different criteria.
     */
    private function groupCategoriesBy(Collection $categories, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $categories->groupBy('is_active')->map(function (Collection $group): array {
                return [
                    'name' => $group->first()->is_active ? 'Activas' : 'Inactivas',
                    'count' => $group->count(),
                    'products_count' => $group->sum('products_count'),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Group electronic invoices by different criteria.
     */
    private function groupElectronicInvoicesBy(Collection $invoices, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $invoices->groupBy('status')->map(function (Collection $group): array {
                return [
                    'name' => ucfirst($group->keys()->first()),
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values()->toArray(),
            'customer' => $invoices->groupBy('customer_id')->map(function (Collection $group): array {
                $customer = $group->first()->customer;
                return [
                    'name' => $customer ? $customer->name : 'N/A',
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values()->toArray(),
            'document_type' => $invoices->groupBy('document_type_id')->map(function (Collection $group): array {
                $docType = $group->first()->documentType;
                return [
                    'name' => $docType ? $docType->name : 'N/A',
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values()->toArray(),
            'date' => $invoices->groupBy(function ($invoice): string {
                return $invoice->created_at->format('Y-m-d');
            })->map(function (Collection $group): array {
                return [
                    'date' => $group->keys()->first(),
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values()->toArray(),
            default => [],
        };
    }

    /**
     * Group suppliers by different criteria.
     */
    private function groupSuppliersBy(Collection $suppliers, string $groupBy): array
    {
        return match ($groupBy) {
            'status' => $suppliers->groupBy('is_active')->map(function (Collection $group): array {
                return [
                    'name' => $group->first()->is_active ? 'Activos' : 'Inactivos',
                    'count' => $group->count(),
                    'products_count' => $group->sum('products_count'),
                ];
            })->values()->toArray(),
            default => [],
        };
    }
}
