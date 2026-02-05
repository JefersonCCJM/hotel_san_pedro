<div>
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-file-invoice-dollar text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Facturas Electrónicas</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="font-semibold text-gray-900">{{ $invoices->total() }}</span> facturas registradas
                        </span>
                        <span class="text-gray-300 hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                            <i class="fas fa-file-invoice mr-1"></i> Facturación electrónica DIAN
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <button wire:click="$dispatch('open-create-electronic-invoice-modal')"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Nueva Factura</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text"
                           wire:model.live="search"
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Buscar por número, cliente, identificación...">
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="status"
                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="1">Aceptadas</option>
                    <option value="0">Pendientes</option>
                    <option value="sent">Enviadas</option>
                    <option value="rejected">Rechazadas</option>
                </select>
                
                <select wire:model.live="perPage"
                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="15">15 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Número
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Identificación
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ $invoice->document }}</div>
                            @if($invoice->reference_code)
                                <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $invoice->reference_code }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $invoice->customer->name }}
                            </div>
                            @if($invoice->customer->taxProfile && $invoice->customer->taxProfile->company)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->company }}</div>
                            @endif
                            @if($invoice->customer->email)
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <i class="fas fa-envelope mr-1"></i>
                                    {{ $invoice->customer->email }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invoice->customer->taxProfile)
                                <div class="text-sm font-semibold text-gray-900">{{ $invoice->customer->taxProfile->identification ?? '-' }}</div>
                                @if($invoice->customer->taxProfile->identificationDocument)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->identificationDocument->name }}</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-400">-</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">
                                ${{ number_format($invoice->total, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusBadge = $this->getStatusBadge($invoice->status);
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusBadge['class'] }} border">
                                <i class="fas {{ $statusBadge['icon'] }} mr-1.5"></i>
                                {{ $statusBadge['text'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $invoice->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('electronic-invoices.show', $invoice) }}"
                                   class="text-blue-600 hover:text-blue-900 font-medium">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($invoice->pdf_url)
                                <a href="{{ $invoice->pdf_url }}" target="_blank"
                                   class="text-green-600 hover:text-green-900 font-medium">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @endif
                                
                                @if($invoice->status === 'pending')
                                <button wire:click="deleteInvoice({{ $invoice->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta factura? Esta acción no se puede deshacer."
                                        class="text-red-600 hover:text-red-900 font-medium">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-invoice text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-sm">No hay facturas registradas</p>
                                <button wire:click="$dispatch('open-create-electronic-invoice-modal')"
                                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    <i class="fas fa-plus mr-2"></i>
                                    Crear primera factura
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($invoices->hasPages())
    <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Mostrando 
            <span class="font-medium">{{ $invoices->firstItem() }}</span> a 
            <span class="font-medium">{{ $invoices->lastItem() }}</span> de 
            <span class="font-medium">{{ $invoices->total() }}</span> resultados
        </div>
        
        <div class="flex items-center space-x-2">
            {{ $invoices->links() }}
        </div>
    </div>
    @endif
</div>
