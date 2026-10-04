<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index($tournamentId = null)
    {
        $tournament = $tournamentId ? Tournament::findOrFail($tournamentId) : Tournament::where('is_current', true)->first();
        if (!$tournament) {
            return redirect()->route('tournaments.wizard');
        }

        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        $teams = Team::with('players')->where('tournament_id', $tournament->id)->get();

        return view('teams.index', compact('tournament', 'allTournaments', 'teams'));
    }

    public function storeTeam(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:10',
            'color' => 'nullable|string',
            'city' => 'nullable|string',
            'coach' => 'nullable|string',
        ]);

        Team::create($validated);

        return redirect()->back()->with('success', 'Equipo creado correctamente.');
    }

    public function storePlayer(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'name' => 'required|string|max:255',
            'dorsal' => 'nullable|integer',
            'position' => 'nullable|string',
        ]);

        Player::create($validated);

        return redirect()->back()->with('success', 'Jugador agregado al equipo.');
    }

    public function destroyTeam($id)
    {
        $team = Team::findOrFail($id);
        $team->delete();

        return redirect()->back()->with('success', 'Equipo eliminado.');
    }

    public function destroyPlayer($id)
    {
        $player = Player::findOrFail($id);
        $player->delete();

        return redirect()->back()->with('success', 'Jugador eliminado.');
    }
}
