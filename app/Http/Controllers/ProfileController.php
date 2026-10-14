<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $allTournaments = Tournament::with('matches')->orderBy('id', 'desc')->get();
        $currentTournament = Tournament::where('is_current', true)->first() ?? $allTournaments->first();

        return view('profile.edit', compact('user', 'allTournaments', 'currentTournament'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'avatar_color' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // If changing password, verify current password
        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors([
                    'current_password' => 'La contraseña actual no es correcta para confirmar el cambio.',
                ])->withInput();
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['avatar_color'])) {
            $user->avatar_color = $validated['avatar_color'];
        }
        $user->country = $validated['country'] ?? 'Venezuela';
        $user->state = $validated['state'] ?? null;
        $user->city = $validated['city'] ?? null;
        $user->bio = $validated['bio'] ?? null;
        $user->save();

        return back()->with('success', '¡Tu perfil ha sido actualizado exitosamente!');
    }
}
