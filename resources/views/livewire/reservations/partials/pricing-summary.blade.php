<!-- Resumen de Cobro -->
<div class="w-full bg-[#1e293b] rounded-[32px] p-6 shadow-2xl border border-slate-700/50 text-white">
    @php($isCreateMode = (bool) ($isCreateMode ?? true))
    @php($paymentMethodLabel = $reservationPaymentMethodLabel ?? (($reservationPaymentMethod ?? 'efectivo') === 'transferencia' ? 'Transferencia' : 'Efectivo'))

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold tracking-tight">Resumen de Cobro</h2>
        <div class="bg-slate-700/50 p-2 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>
    </div>

    <div class="bg-[#0f172a] border border-slate-700 rounded-3xl p-5 mb-6 text-center shadow-inner">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">Total Estancia</p>
        <div class="relative mx-auto max-w-xs">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 font-bold">$</span>
            <input type="number" name="total_amount" wire:model.live="reservation.total" step="1" required
                class="w-full pl-8 pr-4 py-3 bg-slate-800 border border-slate-700 rounded-2xl text-3xl font-bold tracking-tighter text-white text-center focus:ring-2 focus:ring-emerald-500">
        </div>
        @error('reservation.total')
            <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
        @enderror
        @error('total')
            <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
        @enderror

        @if ($autoCalculatedTotal > 0 && (int) $reservationTotal !== (int) $autoCalculatedTotal)
            <button type="button" wire:click="restoreSuggestedTotal"
                class="mt-3 text-[10px] font-bold text-emerald-400 hover:text-emerald-300 underline uppercase tracking-tighter">
                Restaurar total sugerido: ${{ number_format($autoCalculatedTotal, 0, ',', '.') }}
            </button>
        @endif
    </div>

    <div class="space-y-5 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-600/20 p-3 rounded-2xl text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4m0 10V4m-4 6h4" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-slate-100">Abono Inicial</p>
                    <p class="text-xs text-slate-400">Pago reservacion</p>
                </div>
            </div>

            <div class="relative w-36">
                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400">$</span>
                <input type="number" name="deposit" wire:model.live="reservation.deposit" step="1" required
                    class="w-full pl-6 pr-2 py-2 bg-slate-800 border border-slate-700 rounded-xl text-right font-bold text-slate-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        @error('reservation.deposit')
            <p class="text-xs text-red-300">{{ $message }}</p>
        @enderror
        @error('deposit')
            <p class="text-xs text-red-300">{{ $message }}</p>
        @enderror

        @if ($isCreateMode)
            <div class="pt-2">
                <label for="reservation-payment-method" class="block text-xs font-semibold text-slate-300 mb-2">
                    Metodo de pago del abono
                </label>
                <select
                    id="reservation-payment-method"
                    wire:model.live="reservation.paymentMethod"
                    class="w-full py-2 px-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:ring-2 focus:ring-blue-500"
                >
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                </select>
                <p class="mt-2 text-[11px] text-slate-400">
                    Metodo seleccionado:
                    <span class="font-semibold text-slate-200">{{ $paymentMethodLabel }}</span>
                </p>
                @error('reservation.paymentMethod')
                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                @enderror
                @error('payment_method')
                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <div class="flex items-end justify-between mb-6 border-t border-slate-700 pt-5">
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Saldo Pendiente</p>
            <div class="flex items-center space-x-1">
                <span class="text-xl font-light text-slate-500">$</span>
                <span class="text-4xl font-bold tracking-tighter {{ $balance > 0 ? 'text-amber-300' : 'text-white' }}">{{ number_format($balance, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="text-right">
            <span class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-full border {{ $status === 'Liquidado' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20' }}">
                {{ $status }}
            </span>
            <p class="text-[10px] text-slate-500 mt-2 font-medium">Actualizado {{ now()->format('d M, Y') }}</p>
        </div>
    </div>

    @if ((int) $reservationTotal < (int) $reservationDeposit)
        <div class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-xs font-bold text-red-300 text-center uppercase tracking-wide">
            El abono supera el total de la reserva
        </div>
    @endif

    <button type="button" wire:click="downloadReceipt" @disabled(!$isReceiptReady)
        class="w-full py-4 rounded-2xl flex items-center justify-center space-x-3 shadow-lg transition-colors group {{ $isReceiptReady ? 'bg-blue-600 hover:bg-blue-500 shadow-blue-900/20 text-white' : 'bg-slate-700 text-slate-400 cursor-not-allowed' }}">
        <svg xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 transition-transform {{ $isReceiptReady ? 'group-hover:translate-y-0.5' : '' }}"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <span class="font-bold tracking-wide text-base">Descargar Comprobante</span>
    </button>

    @if (!$isReceiptReady)
        <p class="mt-2 text-center text-[11px] text-slate-400">Completa total y abono valido para habilitar la descarga.</p>
    @endif
</div>
