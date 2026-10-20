<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador para el inicio y cierre de sesión de usuarios.
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @return View Vista del login.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Valida las credenciales e inicia la sesión del usuario.
     *
     * @param  Request  $request  Correo y contraseña.
     * @return RedirectResponse Redirección al dashboard o de vuelta al login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(
            [...$credentials, 'estado' => true],
            $request->boolean('remember'),
        )) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Las credenciales no son correctas.'])
            ->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     *
     * @param  Request  $request  Petición actual.
     * @return RedirectResponse Redirección al login.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
