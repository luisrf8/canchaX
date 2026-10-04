<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $sport = $request->input('sport');
        $status = $request->input('status');

        $query = Tournament::with(['organizer', 'teams', 'matches']);

        if ($sport) {
            $query->where('sport', $sport);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $tournaments = $query->orderBy('id', 'desc')->get();
        $organizers = User::where('role', 'organizer')->withCount('tournaments')->get();

        // Metrics
        $totalTournaments = Tournament::count();
        $totalOrganizers = $organizers->count();
        $totalMatches = GameMatch::count();
        $totalTeams = Team::count();

        // Unique Venues / Ubicaciones
        $venues = Tournament::whereNotNull('location')->pluck('location')->unique()->take(6);

        return view('landing', compact(
            'tournaments',
            'organizers',
            'totalTournaments',
            'totalOrganizers',
            'totalMatches',
            'totalTeams',
            'venues',
            'sport',
            'status'
        ));
    }

    public function showOrganizer($id)
    {
        $organizer = User::findOrFail($id);
        $activeTournaments = Tournament::where('user_id', $organizer->id)->where('status', 'active')->get();
        $completedTournaments = Tournament::where('user_id', $organizer->id)->where('status', 'completed')->get();
        $allTournaments = Tournament::where('user_id', $organizer->id)->get();

        return view('organizers.show', compact('organizer', 'activeTournaments', 'completedTournaments', 'allTournaments'));
    }
}
