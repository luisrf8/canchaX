<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CollaboratorController extends Controller
{
    public function store(Request $request, $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        if ($tournament->user_id !== auth()->id()) {
            return back()->withErrors(['auth' => 'Solo el propietario principal del torneo puede añadir colaboradores.']);
        }

        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        if (!$user) {
            if (!$request->input('name') || !$request->input('password')) {
                return back()->withErrors(['email' => 'Este correo no está registrado. Completa el Nombre y la Contraseña para crear el nuevo usuario colaborador.']);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $email,
                'password' => Hash::make($request->input('password')),
                'role' => 'organizer',
                'city' => $tournament->location ?? 'Madrid',
                'bio' => 'Árbitro / Colaborador asignado al torneo ' . $tournament->name,
                'avatar_color' => '#0369a1',
            ]);
        }

        if (!$tournament->collaborators()->where('user_id', $user->id)->exists()) {
            $tournament->collaborators()->attach($user->id, ['created_by_user_id' => auth()->id()]);
        }

        return redirect()->back()->with('success', '¡Colaborador / Árbitro "' . $user->name . '" añadido al torneo exitosamente!');
    }

    public function destroy($tournamentId, $userId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        if ($tournament->user_id !== auth()->id()) {
            return back()->withErrors(['auth' => 'Solo el propietario principal del torneo puede eliminar colaboradores.']);
        }

        $tournament->collaborators()->detach($userId);

        return redirect()->back()->with('success', 'Colaborador removido del torneo.');
    }
}
