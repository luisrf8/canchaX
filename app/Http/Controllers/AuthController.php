<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            return redirect()->intended(route('landing'))->with('success', '¡Bienvenido de nuevo, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:organizer,participant',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
        ]);

        $colors = ['#1e3a8a', '#0369a1', '#b91c1c', '#d97706', '#15803d', '#475569'];
        $avatarColor = $colors[array_rand($colors)];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'country' => $validated['country'] ?? 'Venezuela',
            'state' => $validated['state'] ?? null,
            'city' => $validated['city'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'avatar_color' => $avatarColor,
        ]);

        Auth::login($user, true);

        return redirect()->route('landing')->with('success', '¡Cuenta creada con éxito! Bienvenido a CanchaX.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Has cerrado sesión.');
    }
}
