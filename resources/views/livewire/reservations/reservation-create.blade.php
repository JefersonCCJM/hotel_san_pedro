<div>
    <form wire:submit="createReservation" class="space-y-6">
        @error('general')
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                @include('livewire.reservations.partials.customer-selector', [
                    'customerId' => (string) ($this->reservation->customerId ?: ''),
                    'datesCompleted' => $datesCompleted,
                    'isCustomerLocked' => $isCustomerLocked,
                    'filteredCustomers' => $filteredCustomers,
                    'selectedCustomerInfo' => $selectedCustomerInfo,
                ])

                @include('livewire.reservations.partials.room-selector', [
                    'checkIn' => $this->reservation->checkIn,
                    'checkOut' => $this->reservation->checkOut,
                    'datesCompleted' => $datesCompleted,
                    'areRoomsLocked' => $areRoomsLocked,
                    'availableRooms' => $availableRooms,
                    'selectedRoom' => $selectedRoom,
                    'selectedRoomIds' => $this->reservation->selectedRoomIds,
                    'roomId' => $roomId,
                ])

                <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
                    @php($existingGuestsDocument = $this->existingGuestsDocument ?? null)
                    @php($guestCapacityRules = $this->guestCapacityRules ?? [])
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Detalle de Huespedes (Opcional)</h3>
                        <span class="text-[11px] text-gray-500">Puedes dejarlo vacio</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="reservation-adults" class="block text-xs font-semibold text-gray-600 mb-1">
                                Adultos
                            </label>
                            <input
                                id="reservation-adults"
                                type="number"
                                min="0"
                                step="1"
                                @if (!empty($guestCapacityRules['has_selected_rooms']))
                                    max="{{ (int) ($guestCapacityRules['adults_max'] ?? 0) }}"
                                @endif
                                wire:model.live="reservation.adults"
                                class="w-full border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('reservation.adults') border-red-500 @enderror"
                                placeholder="Ej: 2"
                            >
                            @error('reservation.adults')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reservation-children" class="block text-xs font-semibold text-gray-600 mb-1">
                                Ninos
                            </label>
                            <input
                                id="reservation-children"
                                type="number"
                                min="0"
                                step="1"
                                @if (!empty($guestCapacityRules['has_selected_rooms']))
                                    max="{{ (int) ($guestCapacityRules['children_max'] ?? 0) }}"
                                @endif
                                wire:model.live="reservation.children"
                                class="w-full border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('reservation.children') border-red-500 @enderror"
                                placeholder="Ej: 1"
                            >
                            @error('reservation.children')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if (!empty($guestCapacityRules['has_selected_rooms']))
                        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-xs text-emerald-800 space-y-1">
                            <p>
                                Capacidad total seleccionada:
                                <span class="font-semibold">{{ (int) ($guestCapacityRules['total_capacity'] ?? 0) }}</span>
                                huespedes.
                            </p>
                            <p>
                                Maximo actual de adultos:
                                <span class="font-semibold">{{ (int) ($guestCapacityRules['adults_max'] ?? 0) }}</span>
                                | Maximo actual de ninos:
                                <span class="font-semibold">{{ (int) ($guestCapacityRules['children_max'] ?? 0) }}</span>
                            </p>
                            @if ((int) ($guestCapacityRules['single_bed_rooms'] ?? 0) > 0)
                                <p>
                                    Regla aplicada:
                                    <span class="font-semibold">en habitaciones de 1 cama, maximo 1 nino por habitacion</span>.
                                </p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label for="guests-document" class="block text-xs font-semibold text-gray-600 mb-1">
                            Documento de ingreso de huespedes (Opcional)
                        </label>
                        <input
                            id="guests-document"
                            type="file"
                            wire:model="guestsDocument"
                            class="w-full text-sm border border-gray-300 rounded-lg file:mr-3 file:py-2 file:px-3 file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv"
                        >
                        <p class="mt-1 text-[11px] text-gray-500">
                            Formatos: PDF, imagen, Word, Excel. Maximo 10 MB.
                        </p>

                        @if ($existingGuestsDocument && !$guestsDocument)
                            <div class="mt-2 p-3 rounded-lg border border-blue-200 bg-blue-50 text-xs text-blue-800">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="font-semibold">
                                        Documento actual:
                                        {{ $existingGuestsDocument['name'] ?? 'Documento adjunto' }}
                                    </span>
                                    @if (!empty($existingGuestsDocument['size']) && (int) $existingGuestsDocument['size'] > 0)
                                        <span class="text-blue-700">
                                            ({{ number_format(((int) $existingGuestsDocument['size']) / 1024, 1, ',', '.') }} KB)
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <a href="{{ $existingGuestsDocument['view_url'] ?? '#' }}" target="_blank"
                                        class="inline-flex items-center font-semibold hover:underline text-blue-700">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </a>
                                    <a href="{{ $existingGuestsDocument['download_url'] ?? '#' }}"
                                        class="inline-flex items-center font-semibold hover:underline text-blue-700">
                                        <i class="fas fa-download mr-1"></i> Descargar
                                    </a>
                                </div>

                                @if (isset($existingGuestsDocument['exists']) && !$existingGuestsDocument['exists'])
                                    <p class="mt-2 text-amber-700">
                                        El archivo ya no existe en disco, pero sigue referenciado en la reserva.
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div wire:loading wire:target="guestsDocument" class="mt-1 text-xs text-blue-600">
                            Subiendo documento...
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:loading.inline-flex
                                wire:target="guestsDocument"
                                wire:click="$cancelUpload('guestsDocument')"
                                class="items-center rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                            >
                                Cancelar subida
                            </button>

                            @if ($guestsDocument)
                                <button
                                    type="button"
                                    wire:click="removeGuestsDocument"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Quitar archivo
                                </button>
                            @endif
                        </div>

                        @if ($guestsDocument)
                            <p class="mt-1 text-xs text-emerald-700">
                                Archivo seleccionado:
                                {{ is_object($guestsDocument) && method_exists($guestsDocument, 'getClientOriginalName') ? $guestsDocument->getClientOriginalName() : 'Documento adjunto' }}
                            </p>
                            @if ($existingGuestsDocument)
                                <p class="mt-1 text-xs text-amber-700">
                                    Al guardar, este archivo reemplazara el documento actual.
                                </p>
                            @endif
                        @endif

                        @error('guestsDocument')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <label for="reservation-notes" class="block text-sm font-semibold text-gray-700 mb-2">Notas</label>
                    <textarea id="reservation-notes" wire:model="reservation.notes" rows="3"
                        class="w-full border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('reservation.notes') border-red-500 @enderror"
                        placeholder="Observaciones de la reserva"></textarea>
                    @error('reservation.notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-3 sticky top-24 self-start">
                <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reservations.index') }}"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </a>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove
                                wire:target="createReservation">{{ $this->submitButtonText }}</span>
                            <span wire:loading wire:target="createReservation">Guardando...</span>
                        </button>
                    </div>
                </div>

                @include('livewire.reservations.partials.pricing-summary', [
                    'balance' => $balance,
                    'autoCalculatedTotal' => $autoCalculatedTotal,
                    'isReceiptReady' => $isReceiptReady,
                    'status' => $status,
                    'reservationPaymentMethod' => $reservationPaymentMethod,
                    'reservationPaymentMethodLabel' => $reservationPaymentMethodLabel,
                    'isCreateMode' => $isCreateMode,
                ])
            </div>
        </div>
    </form>

    <livewire:customers.create-customer-modal />
</div>
