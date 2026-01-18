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

        // Validación defensiva: verificar que el usuario existe
        if (!$user) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Sesión inválida. Por favor, inicie sesión nuevamente.');
        }

        // 1. Skip checks for Administrators
        // Usar try-catch para manejar posibles errores con hasRole() de Spatie
        try {
            if ($user->hasRole('Administrador')) {
                return $next($request);
            }
        } catch (\Exception $e) {
            // Si hay un error con hasRole(), loguear y continuar con las validaciones normales
            \Log::warning('Error checking role in SecurityControlMiddleware', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Continuar con el flujo normal (no es Administrador)
        }

        // 2. Control de Turnos Activos (Estricto)
        // Si hay OTRO recepcionista con un turno ACTIVO, este usuario tiene el acceso bloqueado
        $activeShift = \App\Models\ShiftHandover::where('status', \App\Enums\ShiftHandoverStatus::ACTIVE)
            ->where('entregado_por', '!=', $user->id)
            ->first();

        if ($activeShift) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Acceso denegado: El usuario ' . $activeShift->entregadoPor->name . ' tiene un turno activo. Debe entregarlo para que usted pueda ingresar.');
        }

        // 3. IP Restriction
        if ($user->allowed_ip && $request->ip() !== $user->allowed_ip) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Acceso denegado: IP no autorizada.');
        }

        // 4. Schedule Restriction - ELIMINADA por solicitud del usuario
        // Se permite el acceso en cualquier horario, siempre y cuando no haya un turno activo de otro usuario.

        return $next($request);
    }
}
