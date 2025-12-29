<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SecurityControlMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        // Allow bypassing security controls if being impersonated by an Admin
        if (session()->has('impersonated_by')) {
            return $next($request);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Skip checks for Administrators
        if ($user->hasRole('Administrador')) {
            return $next($request);
        }

        // 2. Control de Turnos Activos (Nueva Restricción)
        // Si hay otro recepcionista con un turno ACTIVO, este usuario no puede entrar
        $activeShift = \App\Models\ShiftHandover::where('status', \App\Enums\ShiftHandoverStatus::ACTIVE)
            ->where('entregado_por', '!=', $user->id)
            ->first();

        if ($activeShift) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Acceso denegado: El usuario ' . $activeShift->entregadoPor->name . ' tiene un turno activo en este momento.');
        }

        // 3. IP Restriction
        if ($user->allowed_ip && $request->ip() !== $user->allowed_ip) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Acceso denegado: IP no autorizada.');
        }

        // 4. Schedule Restriction (Restaurada y mejorada)
        if ($user->working_hours) {
            $now = now();
            $startTime = \Carbon\Carbon::createFromTimeString($user->working_hours['start']);
            $endTime = \Carbon\Carbon::createFromTimeString($user->working_hours['end']);

            // Handle overnight shifts (e.g., 22:00 to 06:00)
            if ($endTime->lessThan($startTime)) {
                $isWorkTime = $now->greaterThanOrEqualTo($startTime) || $now->lessThanOrEqualTo($endTime);
            } else {
                $isWorkTime = $now->between($startTime, $endTime);
            }

            if (!$isWorkTime) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Acceso denegado: Fuera de su horario laboral (' . $user->working_hours['start'] . ' - ' . $user->working_hours['end'] . ').');
            }
        }

        return $next($request);

        return $next($request);
    }
}
