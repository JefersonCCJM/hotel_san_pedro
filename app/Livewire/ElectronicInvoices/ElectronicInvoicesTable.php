<?php

namespace App\Livewire\ElectronicInvoices;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ElectronicInvoice;
use Livewire\Attributes\On;

class ElectronicInvoicesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    protected $listeners = [
        'invoice-created' => '$refresh',
        'invoice-updated' => '$refresh',
        'invoice-deleted' => '$refresh',
    ];

    public function mount()
    {
        $this->perPage = 15;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ElectronicInvoice::with(['customer.taxProfile', 'customer.taxProfile.identificationDocument'])
            ->orderBy('created_at', 'desc');

        // Filtro por búsqueda
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('document', 'like', '%' . $this->search . '%')
                  ->orWhere('reference_code', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($subQ) {
                      $subQ->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('customer.taxProfile', function ($subQ) {
                      $subQ->where('identification', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Filtro por estado
        if (!empty($this->status)) {
            $statusMap = [
                '1' => 'accepted',
                '0' => 'pending',
            ];
            $status = $statusMap[$this->status] ?? $this->status;
            if (in_array($status, ['pending', 'sent', 'accepted', 'rejected', 'cancelled'])) {
                $query->where('status', $status);
            }
        }

        $invoices = $query->paginate($this->perPage);

        return view('livewire.electronic-invoices.electronic-invoices-table', [
            'invoices' => $invoices,
        ]);
    }

    public function getStatusBadge($status)
    {
        $badges = [
            'accepted' => [
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check-circle',
                'text' => 'Aceptada'
            ],
            'rejected' => [
                'class' => 'bg-red-50 text-red-700 border-red-200',
                'icon' => 'fa-times-circle',
                'text' => 'Rechazada'
            ],
            'sent' => [
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-paper-plane',
                'text' => 'Enviada'
            ],
            'pending' => [
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
                'text' => 'Pendiente'
            ],
            'cancelled' => [
                'class' => 'bg-gray-50 text-gray-700 border-gray-200',
                'icon' => 'fa-ban',
                'text' => 'Cancelada'
            ],
        ];

        return $badges[$status] ?? $badges['pending'];
    }

    public function deleteInvoice($invoiceId)
    {
        try {
            $invoice = ElectronicInvoice::findOrFail($invoiceId);
            
            // Solo permitir eliminar facturas en estado pendiente
            if ($invoice->status !== 'pending') {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Solo se pueden eliminar facturas en estado pendiente.'
                ]);
                return;
            }

            $invoice->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Factura eliminada exitosamente.'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al eliminar la factura: ' . $e->getMessage()
            ]);
        }
    }
}
