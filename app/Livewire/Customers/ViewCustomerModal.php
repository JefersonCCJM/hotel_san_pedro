<?php

namespace App\Livewire\Customers;

use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class ViewCustomerModal extends Component
{
    public bool $isOpen = false;
    public ?Customer $customer = null;

    protected $listeners = [
        'open-view-customer-modal' => 'open',
    ];

    #[On('open-view-customer-modal')]
    public function open($customerId): void
    {
        // Si viene como array, extraer el ID
        if (is_array($customerId)) {
            $customerId = $customerId['customerId'] ?? null;
        }
        
        Log::info('ViewCustomerModal: open called with customer ID: ' . $customerId);
        
        $this->customer = Customer::with(['taxProfile', 'taxProfile.identificationDocument', 'taxProfile.municipality', 'taxProfile.legalOrganization', 'taxProfile.tribute'])
            ->findOrFail($customerId);
        $this->isOpen = true;
        
        Log::info('ViewCustomerModal: isOpen set to true');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->customer = null;
    }

    public function render()
    {
        return view('livewire.customers.view-customer-modal');
    }
}
