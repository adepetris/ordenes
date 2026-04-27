<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Las credenciales no son validas.'])
                ->onlyInput('username');
        }

        if (! (bool) $request->user()?->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['username' => 'El usuario esta inactivo.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        if ((bool) $request->user()?->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }
}
