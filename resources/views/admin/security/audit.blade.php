@extends('layouts.app')

@section('title', 'Registro de Auditoría')
@section('header', 'Cumplimiento y Seguridad')

@php
    // Map event names to Spanish labels
    $eventLabels = [
        'login' => 'Inicio de Sesión',
        'failed_login' => 'Fallo de Sesión',
        'impersonation_start' => 'Inicio Impersonación',
        'impersonation_end' => 'Fin Impersonación',
        'permission_change' => 'Cambio de Permisos',
        'role_changed' => 'Cambio de Rol',
        'reservation_created' => 'Creación de Reserva',
        'reservation_updated' => 'Actualización de Reserva',
        'reservation_cancelled' => 'Cancelación de Reserva',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <form method="GET" action="{{ route('admin.security.audit') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Evento</label>
                <select name="event" class="w-full rounded-xl border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos los eventos</option>
                    <option value="login" {{ request('event') == 'login' ? 'selected' : '' }}>Inicio de Sesión</option>
                    <option value="failed_login" {{ request('event') == 'failed_login' ? 'selected' : '' }}>Fallo de Sesión</option>
                    <option value="impersonation_start" {{ request('event') == 'impersonation_start' ? 'selected' : '' }}>Inicio Impersonación</option>
                    <option value="permission_change" {{ request('event') == 'permission_change' ? 'selected' : '' }}>Cambio de Permisos</option>
                    <option value="reservation_created" {{ request('event') == 'reservation_created' ? 'selected' : '' }}>Creación de Reserva</option>
                    <option value="reservation_updated" {{ request('event') == 'reservation_updated' ? 'selected' : '' }}>Actualización de Reserva</option>
                    <option value="reservation_cancelled" {{ request('event') == 'reservation_cancelled' ? 'selected' : '' }}>Cancelación de Reserva</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Usuario</label>
                <select name="user_id" class="w-full rounded-xl border-gray-300 text-sm focus:ring-indigo-500">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 bg-gray-800 text-white rounded-xl text-sm font-semibold hover:bg-gray-900 transition-all">
                    <i class="fas fa-search mr-2"></i> Filtrar Auditoría
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Logs -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
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
                @foreach($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">{{ $log->user->name ?? 'Sistema' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $log->event == 'failed_login' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $eventLabels[$log->event] ?? $log->event }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $log->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

