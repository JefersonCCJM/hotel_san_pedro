<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $loginValue = trim($request->login);

        // Determinar si el valor ingresado es un email o un username
        $loginType = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginType => $loginValue,
            'password' => $request->password,
        ];

        // Intentar autenticación
        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        // Si falla, registrar en el log para depuración (solo durante pruebas)
        \Log::warning("Fallo de login para: {$loginValue} (Tipo: {$loginType})");

        throw ValidationException::withMessages([
            'login' => ['Las credenciales proporcionadas no coinciden con nuestros registros.'],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
