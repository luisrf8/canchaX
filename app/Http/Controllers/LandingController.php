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
        $hidePast = $request->boolean('hide_past', false);
        $showHidden = $request->boolean('show_hidden', false);

        $query = Tournament::with(['organizer', 'teams', 'matches']);

        if (!$showHidden) {
            $query->where('is_hidden', false);
        }

        if ($hidePast) {
            $query->where('status', '!=', 'completed')->where('status', '!=', 'archived');
        }

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
            'status',
            'hidePast'
        ));
    }

    public function showOrganizer($id)
    {
        $organizer = User::findOrFail($id);
        $isOwnerOrAdmin = auth()->check() && (auth()->id() === (int)$id || auth()->user()->role === 'admin');

        $baseQuery = Tournament::where('user_id', $organizer->id);
        if (!$isOwnerOrAdmin) {
            $baseQuery->where('is_hidden', false);
        }

        $activeTournaments = (clone $baseQuery)->where('status', 'active')->get();
        $completedTournaments = (clone $baseQuery)->where('status', 'completed')->get();
        $allTournaments = (clone $baseQuery)->get();

        return view('organizers.show', compact('organizer', 'activeTournaments', 'completedTournaments', 'allTournaments'));
    }
}
