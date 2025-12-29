<div class="space-y-6" wire:poll.10s>
    @section('title', 'Registro de Auditoría')
    @section('header', 'Cumplimiento y Seguridad')

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Evento</label>
                <select wire:model.live="event" class="w-full rounded-xl border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos los eventos</option>
                    @foreach(($eventOptions ?? []) as $key)
                        <option value="{{ $key }}">{{ (string) (config('audit.event_aliases.' . $key) ?? $key) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Usuario</label>
                <select wire:model.live="user_id" class="w-full rounded-xl border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <div class="w-full py-2.5 px-4 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-500 flex items-center justify-center">
                    <i class="fas fa-sync-alt fa-spin mr-2"></i> Actualizando cada 10s...
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP / Origen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 font-mono">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold mr-2">
                                        {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $log->user->name ?? 'Sistema' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeClass = (string) (config('audit.badge_classes.' . $log->event) ?? 'bg-indigo-100 text-indigo-800');
                                    $eventLabel = (string) (config('audit.event_aliases.' . $log->event) ?? str_replace('_', ' ', $log->event));
                                @endphp
                                <span class="px-2.5 py-0.5 inline-flex text-[10px] leading-5 font-bold rounded-full uppercase tracking-wider {{ $badgeClass }}">
                                    {{ $eventLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                <div class="flex flex-col">
                                    <span>{{ $log->ip_address }}</span>
                                    <span class="text-[10px] opacity-50 truncate max-w-[150px]">{{ $log->user_agent }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-history text-3xl mb-3 opacity-20"></i>
                                <p>No se encontraron registros de auditoría.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>

