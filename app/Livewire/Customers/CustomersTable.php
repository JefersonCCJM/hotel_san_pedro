<?php

namespace App\Livewire\Customers;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class CustomersTable extends Component
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
        'customer-created' => '$refresh',
        'customer-updated' => '$refresh',
        'customer-deleted' => '$refresh',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->perPage = 15;
        $this->resetPage();
    }

    public function getCustomersProperty()
    {
        $query = Customer::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
        }

        if ($this->status) {
            $query->where('is_active', $this->status === 'active');
        }

        return $query->orderBy('name')
                    ->with(['taxProfile.identificationDocument', 'taxProfile.municipality'])
                    ->paginate($this->perPage);
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->update([
            'is_active' => !$customer->is_active
        ]);

        $status = $customer->is_active ? 'activado' : 'desactivado';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Cliente {$status} correctamente."
        ]);
    }

    public function viewCustomer(int $customerId): void
    {
        Log::info('CustomersTable: viewCustomer called with ID: ' . $customerId);
        
        $this->dispatch('open-view-customer-modal', [
            'customerId' => $customerId
        ]);
    }

    public function render()
    {
        return view('livewire.customers.customers-table', [
            'customers' => $this->customers,
        ]);
    }
}
