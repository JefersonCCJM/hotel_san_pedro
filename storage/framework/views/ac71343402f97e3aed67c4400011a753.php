
<div class="space-y-6" 
     wire:poll.5s="refreshRoomsPolling"
     x-data="{ 
    quickRentModal: <?php if ((object) ('quickRentModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('quickRentModal'->value()); ?>')<?php echo e('quickRentModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('quickRentModal'); ?>')<?php endif; ?>,
        roomDetailModal: <?php if ((object) ('roomDetailModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('roomDetailModal'->value()); ?>')<?php echo e('roomDetailModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('roomDetailModal'); ?>')<?php endif; ?>,
        showCreateCustomerModal: <?php if ((object) ('showCreateCustomerModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showCreateCustomerModal'->value()); ?>')<?php echo e('showCreateCustomerModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showCreateCustomerModal'); ?>')<?php endif; ?>,
        actionsMenuOpen: null,
        actionsMenuPosition: { top: 0, right: 0 },
        init() {
            window.addEventListener('scroll', () => {
                if (this.actionsMenuOpen !== null) {
                    this.closeActionsMenu();
                }
            }, true);
        },
        openActionsMenu(roomId, event) {
            event.stopPropagation();
            if (this.actionsMenuOpen === roomId) {
                this.closeActionsMenu();
                return;
            }
            this.actionsMenuOpen = roomId;
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();
            this.actionsMenuPosition = {
                top: rect.bottom + 8,
                right: window.innerWidth - rect.right
            };
        },
        closeActionsMenu() {
            this.actionsMenuOpen = null;
        }
}"
     @scroll.window="closeActionsMenu()">
    <!-- 1. BLOQUE HEADER -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-door-open text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Habitaciones</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="font-semibold text-gray-900"><?php echo e($rooms->total()); ?></span> habitaciones registradas
                        </span>
                        <span class="text-gray-300 hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                            <i class="fas fa-chart-line mr-1"></i> Panel de control
                        </span>
                    </div>
                </div>
            </div>
            
            <a href="<?php echo e(route('rooms.create')); ?>" 
               wire:navigate
               class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i>
                <span>Nueva Habitación</span>
            </a>
        </div>
    </div>

    <!-- 2. BLOQUE FILTROS -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Buscar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               placeholder="Número o Camas...">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Estado</label>
                    <div class="relative">
                        <select wire:model.live="status"
                                class="block w-full pl-3 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                            <option value="">Todos los estados</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($s->value); ?>"><?php echo e($s->label()); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Ventilación</label>
                    <div class="relative">
                        <select wire:model.live="ventilation_type"
                                class="block w-full pl-3 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                            <option value="">Todos los tipos</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $ventilationTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($vt->value); ?>"><?php echo e($vt->label()); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUE CALENDARIO -->
            <div class="pt-4 border-t border-gray-100">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-3 space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">MES DE CONSULTA</label>
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl p-1">
                            <button wire:click="changeDate('<?php echo e($currentDate->copy()->subMonth()->format('Y-m-d')); ?>')" class="p-2 hover:bg-white hover:shadow-sm rounded-lg transition-all text-gray-400">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <span class="flex-1 text-center text-xs font-bold text-gray-700 uppercase tracking-tighter"><?php echo e($currentDate->translatedFormat('F Y')); ?></span>
                            <button wire:click="changeDate('<?php echo e($currentDate->copy()->addMonth()->format('Y-m-d')); ?>')" class="p-2 hover:bg-white hover:shadow-sm rounded-lg transition-all text-gray-400">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-9 space-y-2">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">DÍAS DEL MES</label>

                        <!-- Vista móvil: scroll horizontal -->
                        <div class="lg:hidden overflow-x-auto pb-2 custom-scrollbar">
                            <div class="flex space-x-2 min-w-max">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $daysInMonth; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php 
                                        $isCurrent = $day->isSameDay($currentDate);
                                        $isToday = $day->isToday();
                                    ?>
                                    <button type="button" wire:click="changeDate('<?php echo e($day->format('Y-m-d')); ?>')"
                                        class="flex flex-col items-center justify-center min-w-[50px] h-14 rounded-xl transition-all border
                                        <?php echo e($isCurrent ? 'bg-blue-600 border-blue-600 text-white shadow-md' : 'bg-gray-50 border-gray-100 text-gray-500 hover:border-blue-200 hover:text-blue-600'); ?>">
                                        <span class="text-[9px] font-bold uppercase tracking-tight"><?php echo e(substr($day->translatedFormat('D'), 0, 1)); ?></span>
                                        <span class="text-sm font-bold mt-0.5"><?php echo e($day->day); ?></span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isToday && !$isCurrent): ?>
                                            <span class="w-1 h-1 bg-blue-500 rounded-full mt-1"></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <!-- Vista desktop: grilla 7 columnas, sin scroll -->
                        <div class="hidden lg:block">
                            <div class="grid grid-cols-7 gap-1">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $daysInMonth; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php 
                                        $isCurrent = $day->isSameDay($currentDate);
                                        $isToday = $day->isToday();
                                    ?>
                                    <button type="button" wire:click="changeDate('<?php echo e($day->format('Y-m-d')); ?>')"
                                        class="flex flex-col items-center justify-center h-12 w-12 rounded-lg transition-all border
                                        <?php echo e($isCurrent ? 'bg-blue-600 border-blue-600 text-white shadow-md' : 'bg-gray-50 border-gray-100 text-gray-500 hover:border-blue-200 hover:text-blue-600'); ?>">
                                        <span class="text-[10px] font-bold uppercase tracking-tight leading-none"><?php echo e(substr($day->translatedFormat('D'), 0, 1)); ?></span>
                                        <span class="text-sm font-bold mt-0.5 leading-tight"><?php echo e($day->day); ?></span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isToday && !$isCurrent): ?>
                                            <span class="w-1 h-1 bg-blue-500 rounded-full mt-1"></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. TABLA DE HABITACIONES -->
    <div class="bg-white rounded-xl border border-gray-100" style="overflow: visible !important;">
        <div class="overflow-x-auto" style="overflow-y: visible !important;">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Habitación</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado de Limpieza</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ventilación</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Huésped Actual / Info</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cuenta</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo e($room->display_status->cardBgColor()); ?> transition-colors duration-150 group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-door-closed"></i>
                                </div>
                                <div wire:click="openRoomDetail(<?php echo e($room->id); ?>)" class="cursor-pointer">
                                    <div class="text-sm font-semibold text-gray-900">Hab. <?php echo e($room->room_number); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo e($room->beds_count); ?> <?php echo e($room->beds_count == 1 ? 'Cama' : 'Camas'); ?> • Cap. <?php echo e($room->max_capacity); ?>

                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?php echo e($room->display_status->color()); ?>">
                                <span class="w-1.5 h-1.5 rounded-full mr-2" style="background-color: currentColor"></span>
                                <?php echo e($room->display_status->label()); ?>

                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php($cleaning = $room->cleaning_status)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $cleaning['color'] }}">
                                <i class="fas {{ $cleaning['icon'] }} mr-1.5"></i>
                                {{ $cleaning['label'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($room->ventilation_type)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    <i class="fas fa-wind mr-1.5"></i>
                                    {{ $room->ventilation_label }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 italic">No asignado</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($room->display_status === \App\Enums\RoomStatus::OCUPADA || $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT)
                                @if(isset($room->current_reservation) && $room->current_reservation)
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-gray-900">{{ $room->current_reservation->customer->name ?? 'N/A' }}</span>
                                        <span class="text-xs text-blue-600 font-medium">
                                            Salida: {{ \Carbon\Carbon::parse($room->current_reservation->check_out_date)->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @elseif(isset($room->guest_name) && $room->guest_name)
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-gray-900">{{ $room->guest_name }}</span>
                                        @if(isset($room->check_out_date) && $room->check_out_date)
                                            <span class="text-xs text-blue-600 font-medium">
                                                Salida: {{ \Carbon\Carbon::parse($room->check_out_date)->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
                                @endif
                            @else
                                <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(($room->display_status === \App\Enums\RoomStatus::OCUPADA || $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) && isset($room->current_reservation) && $room->current_reservation)
                                <div class="flex flex-col space-y-1">
                                    @if($room->is_night_paid)
                                        <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            <i class="fas fa-moon mr-1"></i> NOCHE PAGA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                            <i class="fas fa-moon mr-1"></i> NOCHE PENDIENTE
                                        </span>
                                    @endif

                                    @if($room->total_debt > 0)
                                        <div class="flex flex-col">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Saldo Total</span>
                                            <span class="text-sm font-bold text-red-700">${{ number_format($room->total_debt, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 w-fit">
                                            <i class="fas fa-check-circle mr-1"></i> Al día
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900">${{ number_format($room->active_prices[1] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-xs text-gray-400">precio base</span>
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" style="position: relative; overflow: visible;">
                            @if($currentDate->isPast() && !$currentDate->isToday())
                                <span class="text-xs text-gray-400 italic">Histórico</span>
                            @else
                            <div class="relative inline-block text-right" style="z-index: 100; overflow: visible;">
                                <button type="button"
                                    x-show="actionsMenuOpen !== {{ $room->id }}"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    @click.stop="openActionsMenu({{ $room->id }}, $event)"
                                    @keydown.escape="closeActionsMenu()"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                    style="display: none;"
                                    x-cloak>
                                    <span class="text-lg leading-none">⋮</span>
                                    <span class="sr-only">Acciones</span>
                                </button>

                                <!-- Dropdown profesional debajo de los tres puntos -->
                                <div x-show="actionsMenuOpen === {{ $room->id }}" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                                     @click.outside="closeActionsMenu()"
                                     @click.stop
                                     @keydown.escape.window="closeActionsMenu()"
                                     class="fixed w-56 rounded-lg bg-white shadow-xl ring-1 ring-gray-200 divide-y divide-gray-100"
                                     :style="'z-index: 9999; top: ' + actionsMenuPosition.top + 'px; right: ' + actionsMenuPosition.right + 'px;'"
                                     x-cloak>
                                    <div class="py-1.5" style="overflow: visible !important;">
                                        <button type="button"
                                            wire:click="openQuickRent({{ $room->id }})"
                                            wire:target="openQuickRent({{ $room->id }})"
                                            wire:loading.attr="disabled"
                                            @click.stop="closeActionsMenu()"
                                            class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i class="fas fa-key text-blue-600 mr-3 w-5 text-center"></i>
                                            <span class="flex-1 text-left">Ocupar habitación</span>
                                            <i class="fas fa-spinner fa-spin ml-2 text-xs text-blue-600" wire:loading wire:target="openQuickRent({{ $room->id }})"></i>
                                        </button>
                                        <button type="button"
                                            wire:click="openQuickRent({{ $room->id }})"
                                            wire:target="openQuickRent({{ $room->id }})"
                                            wire:loading.attr="disabled"
                                            @click.stop="closeActionsMenu()"
                                            class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i class="fas fa-calendar-check text-emerald-600 mr-3 w-5 text-center"></i>
                                            <span class="flex-1 text-left">Reservar</span>
                                        </button>
                                        <button type="button"
                                            @click.stop="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }}); closeActionsMenu();"
                                            class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition-colors duration-150">
                                            <i class="fas fa-broom text-yellow-600 mr-3 w-5 text-center"></i>
                                            <span class="flex-1 text-left">Marcar limpieza / liberar</span>
                                        </button>
                                    </div>
                                    <div class="py-1.5" style="overflow: visible !important;">
                                        <a href="{{ route('rooms.edit', $room->id) }}"
                                           @click.stop="closeActionsMenu()"
                                           class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors duration-150">
                                            <i class="fas fa-edit text-indigo-600 mr-3 w-5 text-center"></i>
                                            <span class="flex-1 text-left">Editar habitación</span>
                                        </a>
                                        <button type="button"
                                            wire:click="openRoomDetail({{ $room->id }})"
                                            wire:target="openRoomDetail({{ $room->id }})"
                                            wire:loading.attr="disabled"
                                            @click.stop="closeActionsMenu()"
                                            class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i class="fas fa-history text-gray-500 mr-3 w-5 text-center"></i>
                                            <span class="flex-1 text-left">Ver historial</span>
                                            <i class="fas fa-spinner fa-spin ml-2 text-xs text-gray-500" wire:loading wire:target="openRoomDetail({{ $room->id }})"></i>
                                        </button>
                                        @if($room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT && isset($room->current_reservation) && $room->current_reservation)
                                            <div class="flex border-t border-gray-100 mt-1 pt-1">
                                                <button type="button"
                                                    wire:click="continueStay({{ $room->id }})"
                                                    wire:target="continueStay({{ $room->id }})"
                                                    wire:loading.attr="disabled"
                                                    @click.stop="closeActionsMenu()"
                                                    class="flex-1 flex items-center justify-center px-3 py-2.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <i class="fas fa-redo-alt mr-1.5"></i>
                                                    <span>Continuar</span>
                                                    <i class="fas fa-spinner fa-spin ml-1.5 text-xs" wire:loading wire:target="continueStay({{ $room->id }})"></i>
                                                </button>
                                                <button type="button"
                                                    wire:click="cancelReservation({{ $room->id }})"
                                                    wire:target="cancelReservation({{ $room->id }})"
                                                    wire:loading.attr="disabled"
                                                    @click.stop="closeActionsMenu()"
                                                    class="flex-1 flex items-center justify-center px-3 py-2.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <i class="fas fa-times mr-1.5"></i>
                                                    <span>Cancelar</span>
                                                    <i class="fas fa-spinner fa-spin ml-1.5 text-xs" wire:loading wire:target="cancelReservation({{ $room->id }})"></i>
                                    </button>
                                            </div>
                                @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-door-closed text-4xl text-gray-300 mb-4"></i>
                                <p class="text-base font-semibold text-gray-500 mb-1">No se encontraron habitaciones</p>
                                <p class="text-sm text-gray-400">Registra tu primera habitación para comenzar</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white px-6 py-4 border-t border-gray-100">
            {{ $rooms->links() }}
        </div>
    </div>

    <!-- MODAL: DETALLE CUENTA -->
    <div x-show="roomDetailModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="roomDetailModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
                @if($detailData)
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Habitación {{ $detailData['room']['room_number'] }}</h3>
                    </div>
                    <button @click="roomDetailModal = false" class="text-gray-400 hover:text-gray-900"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="p-8 space-y-8">
                    @if($detailData['reservation'])
                        <div class="space-y-8">
                            <!-- Cards de Resumen -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-4 bg-gray-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Hospedaje</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($detailData['total_hospedaje'], 0, ',', '.') }}</p>
                                </div>
                                <div class="p-4 bg-green-50 rounded-xl text-center relative group">
                                    <p class="text-[9px] font-bold text-green-600 uppercase mb-1">Abono</p>
                                    <p class="text-sm font-bold text-green-700">${{ number_format($detailData['abono_realizado'], 0, ',', '.') }}</p>
                                    <button @click="editDeposit({{ $detailData['reservation']['id'] }}, {{ $detailData['abono_realizado'] }})" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity text-green-600 hover:text-green-800">
                                        <i class="fas fa-edit text-[10px]"></i>
                                    </button>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Consumos</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($detailData['sales_total'], 0, ',', '.') }}</p>
                                </div>
                                <div class="p-4 bg-red-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-red-600 uppercase mb-1">Pendiente</p>
                                    <p class="text-sm font-black text-red-700">${{ number_format($detailData['total_debt'], 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <!-- Sección de Consumos -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Detalle de Consumos</h4>
                                    <button wire:click="toggleAddSale" class="text-[10px] font-bold text-blue-600 uppercase">+ Agregar Consumo</button>
                                </div>

                                @if($showAddSale)
                                <div class="p-6 bg-gray-50 rounded-xl border border-gray-100 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2" wire:ignore>
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Producto</label>
                                            <select wire:model="newSale.product_id" id="detail_product_id" class="w-full"></select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Cantidad</label>
                                            <input type="number" wire:model="newSale.quantity" min="1" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Método de Pago</label>
                                            <select wire:model="newSale.payment_method" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                                <option value="efectivo">Efectivo</option>
                                                <option value="transferencia">Transferencia</option>
                                                <option value="pendiente">Pendiente (Cargar a cuenta)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button wire:click="addSale" class="w-full bg-blue-600 text-white py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm">Cargar Consumo</button>
                                </div>
                                @endif

                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <table class="min-w-full divide-y divide-gray-50">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">Producto</th>
                                                <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Cant</th>
                                                <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Pago</th>
                                                <th class="px-4 py-2 text-right text-[9px] font-bold text-gray-400 uppercase">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($detailData['sales'] as $sale)
                                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                                    <td class="px-4 py-3 text-xs font-bold text-gray-900">{{ $sale['product']['name'] }}</td>
                                                    <td class="px-4 py-3 text-xs text-center font-bold text-gray-500">{{ $sale['quantity'] }}</td>
                                                    <td class="px-4 py-3 text-xs text-center">
                                                        @if($sale['is_paid'])
                                                            <div class="flex flex-col items-center space-y-1">
                                                                <span class="text-[9px] font-bold uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $sale['payment_method'] }}</span>
                                                                <button @click="confirmRevertSale({{ $sale['id'] }})" class="text-[8px] font-bold text-gray-400 underline uppercase tracking-tighter hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">Anular Pago</button>
                                                            </div>
                                                        @else
                                                            <div class="flex flex-col items-center space-y-1">
                                                                <span class="text-[9px] font-bold uppercase text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Pendiente</span>
                                                                <button @click="confirmPaySale({{ $sale['id'] }})" class="text-[8px] font-bold text-blue-600 underline uppercase tracking-tighter hover:text-blue-800">Marcar Pago</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-xs text-right font-black text-gray-900">${{ number_format($sale['total'], 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Historial de Estadía -->
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Estado de Pago por Noches</h4>
                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <table class="min-w-full divide-y divide-gray-50">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">Fecha</th>
                                                <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Valor Noche</th>
                                                <th class="px-4 py-2 text-right text-[9px] font-bold text-gray-400 uppercase">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($detailData['stay_history'] as $stay)
                                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                                    <td class="px-4 py-3 text-xs font-bold text-gray-900">{{ $stay['date'] }}</td>
                                                    <td class="px-4 py-3 text-xs text-center font-bold text-gray-500">${{ number_format($stay['price'], 0, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-xs text-right">
                                                        @if($stay['is_paid'])
                                                            <div class="flex flex-col items-end space-y-1">
                                                                <span class="text-[9px] font-bold uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Pagado</span>
                                                                <button @click="confirmRevertNight({{ $detailData['reservation']['id'] }}, {{ $stay['price'] }})" class="text-[8px] font-bold text-gray-400 underline uppercase tracking-tighter hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">Anular Pago</button>
                                                            </div>
                                                        @else
                                                            <div class="flex flex-col items-end space-y-1">
                                                                <span class="text-[9px] font-bold uppercase text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Pendiente</span>
                                                                <button @click="confirmPayStay({{ $detailData['reservation']['id'] }}, {{ $stay['price'] }})" class="text-[8px] font-bold text-blue-600 underline uppercase tracking-tighter hover:text-blue-800">Pagar Noche</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-calendar-times text-4xl text-gray-200 mb-4"></i>
                            <p class="text-gray-500 font-medium">No hay reserva activa para esta fecha</p>
                        </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL: ARRENDAMIENTO RÁPIDO -->
    <div x-show="quickRentModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="quickRentModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="fas fa-key"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Arrendar Hab. {{ $rentForm['room_number'] }}</h3>
                    </div>
                    <button @click="quickRentModal = false" class="text-gray-400 hover:text-gray-900"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPED PRINCIPAL</label>
                                <button type="button" 
                                        wire:click="$set('showCreateCustomerModal', true)"
                                        class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter flex items-center gap-1">
                                    <i class="fas fa-plus text-[8px]"></i>
                                    Nuevo Cliente
                                </button>
                            </div>
                            <div wire:ignore>
                            <select id="quick_customer_id" class="w-full"></select>
                            </div>
                            @error('rentForm.customer_id')
                                <p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">PERSONAS</label>
                                    <span class="text-[9px] text-gray-500 font-medium">Cap. máx: {{ $rentForm['max_capacity'] ?? 1 }}</span>
                                </div>
                                <input type="number" 
                                       wire:model.live="rentForm.people" 
                                       max="{{ $rentForm['max_capacity'] ?? 1 }}" 
                                       min="1" 
                                       oninput="const max = {{ $rentForm['max_capacity'] ?? 1 }}; const additional = {{ is_array($additionalGuests) ? count($additionalGuests) : 0 }}; const total = parseInt(this.value) + additional; if(total > max) { this.value = Math.max(1, max - additional); } if(this.value > max) { this.value = max; } if(this.value < 1) { this.value = 1; }"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('rentForm.people') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                @php
                                    $additionalGuestsCount = is_array($additionalGuests) ? count($additionalGuests) : 0;
                                    $totalPeople = (int)($rentForm['people'] ?? 1) + $additionalGuestsCount;
                                    $maxCapacity = (int)($rentForm['max_capacity'] ?? 1);
                                    $remaining = $maxCapacity - $totalPeople;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($totalPeople > $maxCapacity): ?>
                                    <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                        Excede la capacidad máxima. Total: <?php echo e($totalPeople); ?>/<?php echo e($maxCapacity); ?>

                                    </p>
                                <?php elseif($remaining > 0): ?>
                                    <p class="text-[10px] text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1 text-[8px]"></i>
                                        Puede agregar <?php echo e($remaining); ?> <?php echo e($remaining == 1 ? 'persona más' : 'personas más'); ?>

                                    </p>
                                <?php else: ?>
                                    <p class="text-[10px] text-emerald-600 mt-1">
                                        <i class="fas fa-check-circle mr-1 text-[8px]"></i>
                                        Capacidad máxima alcanzada (<?php echo e($totalPeople); ?>/<?php echo e($maxCapacity); ?>)
                                    </p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['rentForm.people'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                        <?php echo e($message); ?>

                                    </p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">CHECK-OUT</label>
                                <input type="date" wire:model.live="rentForm.check_out" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold">
                            </div>
                        </div>

                        <!-- Huéspedes Adicionales -->
                        <div class="space-y-2 pt-2 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPEDES ADICIONALES</label>
                                <button type="button" 
                                        wire:click="$set('showCreateCustomerModal', true)"
                                        class="text-[9px] font-bold text-emerald-600 hover:text-emerald-800 uppercase tracking-tighter flex items-center gap-1">
                                    <i class="fas fa-user-plus text-[8px]"></i>
                                    Agregar
                                </button>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($additionalGuests) && is_array($additionalGuests)): ?>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $additionalGuests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex-1">
                                                <p class="text-xs font-bold text-gray-900"><?php echo e($guest['name']); ?></p>
                                                <p class="text-[10px] text-gray-500">ID: <?php echo e($guest['identification']); ?></p>
                                            </div>
                                            <button type="button" 
                                                    wire:click="removeGuest(<?php echo e($index); ?>)"
                                                    class="text-red-500 hover:text-red-700 ml-2">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-[10px] text-gray-400 italic">No hay huéspedes adicionales registrados</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['additionalGuests'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-[10px] text-red-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-4 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Total Hospedaje</p>
                                <input type="number" wire:model="rentForm.total" class="bg-transparent text-lg font-bold text-gray-900 focus:outline-none w-full">
                            </div>
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Abono Inicial</p>
                                <input type="number" wire:model="rentForm.deposit" class="bg-transparent text-lg font-bold text-emerald-600 focus:outline-none w-full">
                            </div>
                            <div class="space-y-1 col-span-2 pt-2 border-t border-gray-200 mt-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Método de Pago del Abono</p>
                                <select wire:model="rentForm.payment_method" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button wire:click="storeQuickRent" 
                            wire:loading.attr="disabled"
                            wire:target="storeQuickRent"
                            class="w-full bg-blue-600 text-white py-4 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="storeQuickRent">Confirmar Arrendamiento</span>
                        <span wire:loading wire:target="storeQuickRent" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR CLIENTE -->
    <div x-show="showCreateCustomerModal" class="fixed inset-0 z-[60] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="showCreateCustomerModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Nuevo Cliente</h3>
                    </div>
                    <button @click="showCreateCustomerModal = false" class="text-gray-400 hover:text-gray-900">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nombre Completo <span class="text-red-500">*</span></label>
                        <input type="text" 
                               wire:model.blur="newCustomer.name"
                               oninput="this.value = this.value.toUpperCase(); this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\-]/g, '');"
                               minlength="2"
                               maxlength="255"
                               required
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\-]{2,255}"
                               class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold uppercase mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php $__errorArgs = ['newCustomer.name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 focus:ring-red-500 focus:border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['newCustomer.name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                <?php echo e($message); ?>

                            </p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Identificación <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   wire:model.blur="newCustomer.identification"
                                   oninput="this.value = this.value.replace(/\D/g, '');"
                                   minlength="6"
                                   maxlength="10"
                                   required
                                   pattern="\d{6,10}"
                                   placeholder="6-10 dígitos"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php $__errorArgs = ['newCustomer.identification'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 focus:ring-red-500 focus:border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['newCustomer.identification'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                    <?php echo e($message); ?>

                                </p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Teléfono</label>
                            <input type="text" 
                                   wire:model.blur="newCustomer.phone"
                                   oninput="this.value = this.value.replace(/\D/g, ''); if(this.value.length > 10) this.value = this.value.slice(0, 10);"
                                   maxlength="10"
                                   pattern="\d{10}"
                                   placeholder="10 dígitos"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php $__errorArgs = ['newCustomer.phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 focus:ring-red-500 focus:border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['newCustomer.phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                    <?php echo e($message); ?>

                                </p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                        <input type="email" 
                               wire:model.blur="newCustomer.email"
                               oninput="if(this.value && !this.value.includes('@')) { this.setCustomValidity('El correo debe contener un símbolo @'); } else { this.setCustomValidity(''); }"
                               maxlength="255"
                               pattern="[^\s@]+@[^\s@]+\.[^\s@]+"
                               placeholder="usuario@dominio.com"
                               class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php $__errorArgs = ['newCustomer.email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 focus:ring-red-500 focus:border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['newCustomer.email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                <?php echo e($message); ?>

                            </p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="button" 
                            wire:click="createCustomer"
                            wire:loading.attr="disabled"
                            wire:target="createCustomer"
                            class="flex-1 bg-blue-600 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="createCustomer">Crear Cliente</span>
                        <span wire:loading wire:target="createCustomer" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Creando...
                        </span>
                    </button>
                    <button type="button" 
                            wire:click="addGuest"
                            wire:loading.attr="disabled"
                            wire:target="addGuest"
                            class="flex-1 bg-emerald-600 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="addGuest">Agregar como Huésped</span>
                        <span wire:loading wire:target="addGuest" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Agregando...
                        </span>
                    </button>
                    <button type="button" 
                            @click="showCreateCustomerModal = false"
                            class="px-4 bg-gray-100 text-gray-700 py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            let customerSelect = null;
            let productSelect = null;

            Livewire.on('notify', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                Swal.fire({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                    icon: payload.type || 'info', 
                    title: payload.message || ''
                });
            });

            Livewire.on('initAddSaleSelect', () => {
                setTimeout(() => {
                    if (productSelect) productSelect.destroy();
                    productSelect = new TomSelect('#detail_product_id', {
                        valueField: 'id', labelField: 'name', searchField: ['name', 'sku'], loadThrottle: 400, placeholder: 'Buscar...',
                        preload: true,
                        load: (query, callback) => {
                            fetch(`/api/products/search?q=${encodeURIComponent(query)}`).then(r => r.json()).then(j => callback(j.results)).catch(() => callback());
                        },
                        onChange: (val) => { window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('newSale.product_id', val); },
                        render: {
                            option: (i, e) => `
                                <div class="px-4 py-2 border-b border-gray-50 flex justify-between items-center hover:bg-blue-50 transition-colors">
                                    <div>
                                        <div class="font-bold text-gray-900">${e(i.name)}</div>
                                        <div class="text-[10px] text-gray-400 uppercase tracking-wider">SKU: ${e(i.sku)} | Stock: ${e(i.quantity || i.stock)}</div>
                                    </div>
                                    <div class="text-blue-600 font-bold">${new Intl.NumberFormat('es-CO').format(i.price)}</div>
                                </div>`,
                            item: (i, e) => `<div class="font-bold text-blue-700">${e(i.name)}</div>`
                        }
                    });
                }, 100);
            });

            Livewire.on('quickRentOpened', () => {
                setTimeout(() => {
                    if (customerSelect) customerSelect.destroy();
                    customerSelect = new TomSelect('#quick_customer_id', {
                        valueField: 'id', 
                        labelField: 'name', 
                        searchField: ['name', 'identification', 'text'], 
                        loadThrottle: 400, 
                        placeholder: 'Buscar cliente...',
                        preload: true,
                        load: (query, callback) => {
                            fetch(`/api/customers/search?q=${encodeURIComponent(query || '')}`)
                                .then(r => r.json())
                                .then(j => {
                                    const results = j.results || [];
                                    callback(results);
                                })
                                .catch(() => callback());
                        },
                        onChange: (val) => { 
                            if (val) {
                                window.Livewire.find('<?php echo e($_instance->getId()); ?>').set('rentForm.customer_id', val);
                            }
                        },
                        render: {
                            option: (item, escape) => {
                                const name = escape(item.name || item.text || '');
                                const id = escape(item.identification || '');
                                return `<div class="px-4 py-2 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                                    <div class="font-bold text-gray-900">${name}</div>
                                    ${id ? `<div class="text-[10px] text-gray-500 mt-0.5">ID: ${escape(id)}</div>` : ''}
                                </div>`;
                            },
                            item: (item, escape) => {
                                return `<div class="font-bold text-blue-700">${escape(item.name || item.text || '')}</div>`;
                            },
                            no_results: () => {
                                return '<div class="px-4 py-2 text-gray-500 text-sm">No se encontraron clientes</div>';
                            }
                        }
                    });
                }, 100);
            });

            Livewire.on('customerCreated', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                const customerId = payload?.customerId || payload;
                if (customerSelect && customerId) {
                    // Reload options to include the new customer
                    customerSelect.load((query, callback) => {
                        fetch(`/api/customers/search?q=`)
                            .then(r => r.json())
                            .then(j => callback(j.results || []))
                            .catch(() => callback());
                    });
                    // Set the new customer as selected
                    setTimeout(() => {
                        customerSelect.setValue(customerId);
                    }, 200);
                }
            });
        });

        function confirmRelease(roomId, roomNumber, totalDebt, reservationId) {
            // Use parameters passed directly from the button click
            const hasDebt = totalDebt && totalDebt > 0;
            const validReservationId = reservationId && reservationId !== 'null' ? reservationId : null;

            if (hasDebt && validReservationId) {
                Swal.fire({
                    title: '¡Habitación con Deuda!',
                    html: `La habitación #${roomNumber} tiene una deuda pendiente de <b>${new Intl.NumberFormat('es-CO', {style:'currency', currency:'COP', minimumFractionDigits:0}).format(totalDebt)}</b>.<br><br>¿Desea marcar todo como pagado antes de liberar?`,
                    icon: 'warning',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Pagar Todo y Continuar',
                    denyButtonText: 'Liberar con Deuda',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#10b981',
                    denyButtonColor: '#f59e0b',
                    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', denyButton: 'rounded-xl', cancelButton: 'rounded-xl' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Preguntar método de pago para "Pagar Todo"
                        Swal.fire({
                            title: 'Método de Pago',
                            text: '¿Cómo se salda la deuda total?',
                            icon: 'question',
                            showDenyButton: true,
                            confirmButtonText: 'Efectivo',
                            denyButtonText: 'Transferencia',
                            confirmButtonColor: '#10b981',
                            denyButtonColor: '#3b82f6',
                            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', denyButton: 'rounded-xl' }
                        }).then((payResult) => {
                            if (payResult.isConfirmed || payResult.isDenied) {
                                const method = payResult.isConfirmed ? 'efectivo' : 'transferencia';
                                window.Livewire.find('<?php echo e($_instance->getId()); ?>').payEverything(validReservationId, method).then(() => {
                                    showReleaseOptions(roomId, roomNumber);
                                });
                            }
                        });
                    } else if (result.isDenied) {
                        showReleaseOptions(roomId, roomNumber);
                    }
                });
            } else {
                showReleaseOptions(roomId, roomNumber);
            }
        }

        function showReleaseOptions(roomId, roomNumber) {
            const livewireComponent = window.Livewire.find('<?php echo e($_instance->getId()); ?>');
            
            Swal.fire({
                title: 'Liberar Habitación #' + roomNumber,
                html: '<p class="text-gray-600 mb-6">¿En qué estado desea dejar la habitación?</p>' +
                      '<div class="flex flex-col gap-3 mt-4" id="swal-release-buttons">' +
                      '<button type="button" data-action="libre" class="swal-release-btn w-full py-3 px-6 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl transition-colors duration-200">Libre</button>' +
                      '<button type="button" data-action="pendiente_aseo" class="swal-release-btn w-full py-3 px-6 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-colors duration-200">Pendiente por Aseo</button>' +
                      '<button type="button" data-action="limpia" class="swal-release-btn w-full py-3 px-6 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl transition-colors duration-200">Limpia</button>' +
                      '</div>',
                icon: 'question',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#6b7280',
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl', cancelButton: 'rounded-xl' },
                didOpen: () => {
                    setTimeout(() => {
                        const container = document.querySelector('#swal-release-buttons');
                        if (container) {
                            container.addEventListener('click', function(e) {
                                const btn = e.target.closest('.swal-release-btn');
                                if (btn) {
                                    const action = btn.getAttribute('data-action');
                                    Swal.close();
                                    livewireComponent.call('releaseRoom', roomId, action);
                                }
                            });
                        }
                    }, 50);
                }
            });
        }

        function confirmPaySale(saleId) {
            Swal.fire({
                title: 'Registrar Pago de Consumo',
                icon: 'question',
                showDenyButton: true, confirmButtonText: 'Efectivo', denyButtonText: 'Transferencia',
                confirmButtonColor: '#10b981', denyButtonColor: '#3b82f6',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', denyButton: 'rounded-xl' }
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').paySale(saleId, result.isConfirmed ? 'efectivo' : 'transferencia');
                }
            });
        }

        function confirmRevertSale(saleId) {
            Swal.fire({
                title: 'Anular Pago de Consumo',
                icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Sí, anular',
                confirmButtonColor: '#ef4444',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' }
            }).then((result) => {
                if (result.isConfirmed) { window.Livewire.find('<?php echo e($_instance->getId()); ?>').paySale(saleId, 'pendiente'); }
            });
        }

        function confirmPayStay(reservationId, amount) {
            Swal.fire({
                title: 'Pagar Noche de Hospedaje',
                text: '¿Cómo desea registrar el pago de esta noche?',
                icon: 'info',
                showDenyButton: true,
                confirmButtonText: 'Efectivo',
                denyButtonText: 'Transferencia',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#3b82f6',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', denyButton: 'rounded-xl' }
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    const method = result.isConfirmed ? 'efectivo' : 'transferencia';
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').payNight(reservationId, amount, method);
                }
            });
        }

        function confirmRevertNight(reservationId, amount) {
            Swal.fire({
                title: 'Anular Pago de Noche',
                text: "¿Desea descontar el valor de esta noche del abono total?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.Livewire.find('<?php echo e($_instance->getId()); ?>').revertNightPayment(reservationId, amount);
                }
            });
        }

        function editDeposit(reservationId, current) {
            Swal.fire({
                title: 'Modificar Abono',
                input: 'number', inputValue: current,
                showCancelButton: true, confirmButtonText: 'Actualizar', confirmButtonColor: '#10b981',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' }
            }).then((result) => {
                if (result.isConfirmed) { window.Livewire.find('<?php echo e($_instance->getId()); ?>').updateDeposit(reservationId, result.value); }
            });
        }
    </script>
    <?php $__env->stopPush(); ?>
</div>

<?php /**PATH C:\Users\crist\Documents\aparte\hotel_san_pedro\resources\views/livewire/room-manager.blade.php ENDPATH**/ ?>