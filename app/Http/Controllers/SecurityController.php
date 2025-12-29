<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityController extends Controller
{
    /**
     * Display the permissions matrix.
     */
    public function permissionsMatrix()
    {
        $this->authorize('manage_roles');

        $roles = Role::all();
        $permissions = Permission::all()->groupBy(function($p) {
            // Group permissions by category (first part of name)
            $parts = explode('_', $p->name);
            return count($parts) > 1 ? end($parts) : 'otras';
        });

        return view('admin.security.permissions', compact('roles', 'permissions'));
    }

    /**
     * Update permissions for a role.
     */
    public function updatePermissions(Request $request)
    {
        $this->authorize('manage_roles');

        $data = $request->validate([
            'permissions' => 'array',
            'permissions.*.*' => 'boolean'
        ]);

        foreach ($data['permissions'] as $roleId => $permissions) {
            $role = Role::findById($roleId);
            $permissionNames = array_keys(array_filter($permissions));
            $role->syncPermissions($permissionNames);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'permission_change',
            'description' => 'Actualización masiva de permisos de roles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Permisos actualizados correctamente.');
    }

    /**
     * Impersonate a user.
     */
    public function startImpersonation(User $user)
    {
        $this->authorize('manage_roles');

        if ($user->hasRole('Administrador')) {
            return back()->with('error', 'No se permite impersonar a otro administrador.');
        }

        $adminId = Auth::id();
        
        session()->put('impersonated_by', $adminId);
        
        AuditLog::create([
            'user_id' => $adminId,
            'event' => 'impersonation_start',
            'description' => "Inició impersonación del usuario: {$user->name} ({$user->email})",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => ['target_user_id' => $user->id]
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "Ahora estás viendo el sistema como {$user->name}");
    }

    /**
     * Stop impersonating.
     */
    public function stopImpersonation()
    {
        if (!session()->has('impersonated_by')) {
            return redirect()->route('dashboard');
        }

        $adminId = session()->pull('impersonated_by');
        $admin = User::find($adminId);
        
        $currentUser = Auth::user();

        AuditLog::create([
            'user_id' => $adminId,
            'event' => 'impersonation_end',
            'description' => "Finalizó impersonación del usuario: {$currentUser->name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Auth::login($admin);

        return redirect()->route('roles.index')->with('success', 'Has vuelto a tu sesión de administrador.');
    }

    /**
     * Verify the user's security PIN for critical actions.
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4'
        ]);

        $user = Auth::user();
        
        if ($user->verifyPin($request->pin)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'PIN incorrecto.'], 403);
    }
}
