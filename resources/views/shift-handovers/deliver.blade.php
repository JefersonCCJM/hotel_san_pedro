@extends('layouts.app')

@section('title', 'Entregar Turno')
@section('header', 'Entregar Turno')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    {{-- Resumen financiero del turno --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <h3 class="font-bold text-gray-900 mb-4 uppercase text-xs tracking-wider border-b pb-2">Resumen del Turno</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-center">
                <p class="text-[10px] text-gray-500 uppercase font-bold">Base Inicial</p>
                <p class="text-lg font-bold text-gray-900">${{ number_format($activeShift->base_inicial, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-center">
                <p class="text-[10px] text-emerald-600 uppercase font-bold">Ventas Efectivo</p>
                <p class="text-lg font-bold text-emerald-600">${{ number_format($activeShift->total_entradas_efectivo, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 text-center">
                <p class="text-[10px] text-blue-600 uppercase font-bold">Ventas Transferencia</p>
                <p class="text-lg font-bold text-blue-600">${{ number_format($activeShift->total_entradas_transferencia, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-red-50 border border-red-100 text-center">
                <p class="text-[10px] text-red-600 uppercase font-bold">Total Salidas</p>
                <p class="text-lg font-bold text-red-600">${{ number_format($activeShift->total_salidas, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 text-center">
                <p class="text-[10px] text-indigo-600 uppercase font-bold">Base Esperada</p>
                <p class="text-xl font-black text-indigo-600">${{ number_format($activeShift->base_esperada, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Conteos rapidos --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-emerald-600">{{ $activeShift->sales->count() }}</p>
            <p class="text-xs text-gray-500 font-medium">Ventas</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-red-600">{{ $activeShift->cashOutflows->count() }}</p>
            <p class="text-xs text-gray-500 font-medium">Gastos</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-purple-600">{{ $activeShift->productOuts->count() }}</p>
            <p class="text-xs text-gray-500 font-medium">Salidas Producto</p>
        </div>
    </div>

    {{-- Detalle de ventas (colapsable) --}}
    @if($activeShift->sales->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ open: false }">
        <button @click="open = !open" class="w-full px-6 py-4 border-b border-gray-100 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <h3 class="font-bold text-gray-900 uppercase text-xs tracking-wider">
                <i class="fas fa-shopping-cart mr-2 text-emerald-500"></i>Detalle de Ventas ({{ $activeShift->sales->count() }})
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        <div x-show="open" x-collapse>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase">Hora</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase">Productos</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black text-gray-500 uppercase">Metodo</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($activeShift->sales->sortByDesc('created_at') as $sale)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $sale->created_at->format('H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @foreach($sale->items->take(3) as $item)
                                    <span class="text-xs">{{ $item->quantity }}x {{ $item->product->name ?? 'N/A' }}</span>@if(!$loop->last), @endif
                                @endforeach
                                @if($sale->items->count() > 3)
                                    <span class="text-xs text-gray-400">+{{ $sale->items->count() - 3 }} mas</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ match($sale->payment_method) { 'efectivo' => 'bg-emerald-100 text-emerald-700', 'transferencia' => 'bg-blue-100 text-blue-700', 'ambos' => 'bg-purple-100 text-purple-700', default => 'bg-amber-100 text-amber-700' } }}">
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-gray-900">${{ number_format($sale->total, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Detalle de gastos (colapsable) --}}
    @if($activeShift->cashOutflows->isNotEmpty())
    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ open: false }">
            <button @click="open = !open" class="w-full px-6 py-4 border-b border-gray-100 flex items-center justify-between hover:bg-gray-50 transition-colors">
                <h3 class="font-bold text-gray-900 uppercase text-xs tracking-wider">
                    <i class="fas fa-money-bill-wave mr-2 text-red-500"></i>Gastos ({{ $activeShift->cashOutflows->count() }})
                </h3>
                <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($activeShift->cashOutflows->sortByDesc('created_at') as $outflow)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $outflow->reason }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-red-600">${{ number_format($outflow->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Formulario de entrega --}}
    <div class="bg-white rounded-xl border-2 border-blue-200 p-6 shadow-sm">
        <h3 class="font-bold text-gray-900 mb-6 uppercase text-xs tracking-wider border-b pb-2">
            <i class="fas fa-hand-holding-usd mr-2 text-blue-500"></i>Confirmar Entrega del Turno
        </h3>
        <form action="{{ route('shift.end') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Final en Caja ($)</label>
                    <input type="text" name="base_final"
                           value="{{ number_format($activeShift->base_esperada, 0, ',', '.') }}"
                           oninput="formatNumberInput(this)"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-lg font-bold"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Base esperada: ${{ number_format($activeShift->base_esperada, 0, ',', '.') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Receptor (opcional)</label>
                    <select name="recibido_por" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Cualquier recepcionista --</option>
                        @foreach($receivers as $receiver)
                            <option value="{{ $receiver->id }}">{{ $receiver->name }} ({{ $receiver->roles->first()->name ?? '' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones de entrega</label>
                <textarea name="observaciones" rows="3"
                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Novedades, pendientes, observaciones del turno..."></textarea>
            </div>
            <div class="flex gap-3 mt-6">
                <a href="{{ url()->previous() }}" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-200 transition-colors text-center">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check mr-2"></i> Confirmar Entrega del Turno
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
