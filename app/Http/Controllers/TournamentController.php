<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\MatchIncident;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\TournamentCreatedNotification;
use App\Services\TournamentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TournamentController extends Controller
{
    public function index()
    {
        $currentTournament = Tournament::where('is_current', true)->first() 
            ?? Tournament::orderBy('id', 'desc')->first();

        if (!$currentTournament) {
            return redirect()->route('tournaments.wizard');
        }

        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        $teams = Team::where('tournament_id', $currentTournament->id)->get();
        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'winner'])
            ->where('tournament_id', $currentTournament->id)
            ->orderBy('id', 'asc')
            ->get();

        $liveMatches = $matches->filter(function ($m) {
            return $m->status === 'live' && $m->current_period !== 'Finalizado';
        });
        $finishedMatches = $matches->filter(function ($m) {
            return $m->status === 'finished' || $m->current_period === 'Finalizado';
        });
        $totalGoals = $matches->sum('home_score') + $matches->sum('away_score');
        $totalPlayers = Player::whereIn('team_id', $teams->pluck('id'))->count();

        $leader = null;
        if ($currentTournament->format === 'knockout') {
            $finalMatch = $matches->where('stage', 'Gran Final')->first();
            if ($finalMatch && $finalMatch->winner) {
                $leader = $finalMatch->winner;
            }
        }

        return view('tournaments.dashboard', compact(
            'currentTournament',
            'allTournaments',
            'teams',
            'matches',
            'liveMatches',
            'finishedMatches',
            'totalGoals',
            'totalPlayers',
            'leader'
        ));
    }

    public function switchTournament($id)
    {
        Tournament::query()->update(['is_current' => false]);
        $tournament = Tournament::findOrFail($id);
        $tournament->update(['is_current' => true]);

        return redirect()->route('dashboard')->with('success', 'Torneo cambiado a: ' . $tournament->name);
    }

    public function wizard()
    {
        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        return view('tournaments.wizard', compact('allTournaments'));
    }

    public function storeWizard(Request $request, TournamentEngine $engine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sport' => 'required|string',
            'format' => 'required|string',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'period_duration' => 'nullable|integer|min:1',
            'extra_period_duration' => 'nullable|integer|min:1',
            'knockout_tiebreak' => 'nullable|string',
            'tiebreak_priority' => 'nullable|array',
            'stage_rules' => 'nullable|array',
            'teams' => 'required|array|min:2',
            'teams.*.name' => 'required|string|max:255',
            'teams.*.short_name' => 'required|string|max:10',
            'teams.*.color' => 'nullable|string',
            'teams.*.city' => 'nullable|string',
            'teams.*.coach' => 'nullable|string',
        ]);

        Tournament::query()->update(['is_current' => false]);

        $settings = [
            'period_duration' => (int) ($validated['period_duration'] ?? ($validated['sport'] === 'fifa' ? 6 : 45)),
            'extra_period_duration' => (int) ($validated['extra_period_duration'] ?? ($validated['sport'] === 'fifa' ? 3 : 15)),
            'knockout_tiebreak' => $validated['knockout_tiebreak'] ?? 'prorroga_penales',
            'tiebreak_priority' => $validated['tiebreak_priority'] ?? ['direct', 'dg', 'gf', 'gc', 'fair_play'],
            'stage_rules' => $validated['stage_rules'] ?? [
                '16vos' => ['tiebreak' => 'penales_directo', 'leg_type' => 'single'],
                'octavos' => ['tiebreak' => 'penales_directo', 'leg_type' => 'single'],
                'cuartos' => ['tiebreak' => 'penales_directo', 'leg_type' => 'single'],
                'semis' => ['tiebreak' => 'penales_directo', 'leg_type' => 'single'],
                'final' => ['tiebreak' => 'prorroga_penales', 'leg_type' => 'single'],
            ],
        ];

        $tournament = Tournament::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'sport' => $validated['sport'],
            'format' => $validated['format'],
            'location' => $validated['location'] ?? 'Sede Central',
            'description' => $validated['description'] ?? null,
            'status' => 'active',
            'is_current' => true,
            'settings' => $settings,
        ]);

        $engine->generateTournamentStructure($tournament, $validated['teams']);

        // Notify all users about the new tournament in real-time
        Notification::send(User::all(), new TournamentCreatedNotification($tournament));

        return redirect()->route('dashboard')->with('success', '¡Torneo creado exitosamente!');
    }

    public function bracket($id = null)
    {
        $tournament = $id ? Tournament::findOrFail($id) : Tournament::where('is_current', true)->first();
        if (!$tournament) {
            return redirect()->route('tournaments.wizard');
        }

        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'winner'])
            ->where('tournament_id', $tournament->id)
            ->orderBy('id', 'asc')
            ->get();

        return view('tournaments.bracket', compact('tournament', 'allTournaments', 'matches'));
    }

    public function standings($id = null)
    {
        $tournament = $id ? Tournament::findOrFail($id) : Tournament::where('is_current', true)->first();
        if (!$tournament) {
            return redirect()->route('tournaments.wizard');
        }

        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        $teams = Team::where('tournament_id', $tournament->id)->get();
        $matches = GameMatch::where('tournament_id', $tournament->id)->get();

        // Calculate Fair Play Points
        $cardIncidents = MatchIncident::whereHas('gameMatch', function ($q) use ($tournament) {
                $q->where('tournament_id', $tournament->id);
            })
            ->whereIn('type', ['card_yellow', 'card_red'])
            ->get();

        $fairPlayScores = [];
        foreach ($teams as $t) {
            $fairPlayScores[$t->id] = 0;
        }
        foreach ($cardIncidents as $c) {
            if (isset($fairPlayScores[$c->team_id])) {
                $fairPlayScores[$c->team_id] += ($c->type === 'card_yellow') ? 1 : 3;
            }
        }

        // Head-to-head scores index
        $headToHead = [];
        foreach ($matches->where('status', 'finished') as $m) {
            $key = $m->home_team_id . '_' . $m->away_team_id;
            $revKey = $m->away_team_id . '_' . $m->home_team_id;

            if ($m->home_score > $m->away_score) {
                $headToHead[$key] = 3;
                $headToHead[$revKey] = 0;
            } elseif ($m->away_score > $m->home_score) {
                $headToHead[$key] = 0;
                $headToHead[$revKey] = 3;
            } else {
                $headToHead[$key] = 1;
                $headToHead[$revKey] = 1;
            }
        }

        // Base Standings
        $standings = [];
        foreach ($teams as $team) {
            $standings[$team->id] = [
                'team' => $team,
                'pj' => 0,
                'pg' => 0,
                'pe' => 0,
                'pp' => 0,
                'gf' => 0,
                'gc' => 0,
                'dg' => 0,
                'pts' => 0,
                'fair_play' => $fairPlayScores[$team->id] ?? 0,
            ];
        }

        foreach ($matches->where('status', 'finished') as $m) {
            if (isset($standings[$m->home_team_id]) && isset($standings[$m->away_team_id])) {
                $h = &$standings[$m->home_team_id];
                $a = &$standings[$m->away_team_id];

                $h['pj']++;
                $a['pj']++;
                $h['gf'] += $m->home_score;
                $h['gc'] += $m->away_score;
                $a['gf'] += $m->away_score;
                $a['gc'] += $m->home_score;

                if ($m->home_score > $m->away_score) {
                    $h['pg']++;
                    $h['pts'] += 3;
                    $a['pp']++;
                } elseif ($m->away_score > $m->home_score) {
                    $a['pg']++;
                    $a['pts'] += 3;
                    $h['pp']++;
                } else {
                    $h['pe']++;
                    $a['pe']++;
                    $h['pts'] += 1;
                    $a['pts'] += 1;
                }

                $h['dg'] = $h['gf'] - $h['gc'];
                $a['dg'] = $a['gf'] - $a['gc'];
            }
        }

        // Priority array from tournament settings
        $priorityOrder = $tournament->settings['tiebreak_priority'] ?? ['direct', 'dg', 'gf', 'gc', 'fair_play'];

        usort($standings, function ($a, $b) use ($priorityOrder, $headToHead) {
            if ($b['pts'] !== $a['pts']) {
                return $b['pts'] <=> $a['pts'];
            }

            foreach ($priorityOrder as $criterion) {
                if ($criterion === 'direct') {
                    $key = $a['team']->id . '_' . $b['team']->id;
                    $scoreA = $headToHead[$key] ?? 0;
                    $scoreB = $headToHead[$b['team']->id . '_' . $a['team']->id] ?? 0;
                    if ($scoreA !== $scoreB) return $scoreB <=> $scoreA;
                } elseif ($criterion === 'dg') {
                    if ($b['dg'] !== $a['dg']) return $b['dg'] <=> $a['dg'];
                } elseif ($criterion === 'gf') {
                    if ($b['gf'] !== $a['gf']) return $b['gf'] <=> $a['gf'];
                } elseif ($criterion === 'gc') {
                    if ($a['gc'] !== $b['gc']) return $a['gc'] <=> $b['gc']; // Lower goals against is better
                } elseif ($criterion === 'fair_play') {
                    if ($a['fair_play'] !== $b['fair_play']) return $a['fair_play'] <=> $b['fair_play']; // Lower cards score is better
                }
            }

            return $b['dg'] <=> $a['dg'];
        });

        // Top Scorers
        $incidents = MatchIncident::with(['player', 'team'])
            ->whereHas('gameMatch', function ($q) use ($tournament) {
                $q->where('tournament_id', $tournament->id);
            })
            ->whereIn('type', ['goal', 'point_tennis', 'point_basket_1', 'point_basket_2', 'point_basket_3'])
            ->get();

        $scorersData = [];
        foreach ($incidents as $inc) {
            if ($inc->player) {
                $pid = $inc->player->id;
                if (!isset($scorersData[$pid])) {
                    $scorersData[$pid] = [
                        'player' => $inc->player,
                        'team' => $inc->team,
                        'goals' => 0,
                        'assists' => 0,
                    ];
                }
                $scorersData[$pid]['goals']++;
            }
        }

        $assistIncidents = MatchIncident::with(['assistPlayer', 'team'])
            ->whereHas('gameMatch', function ($q) use ($tournament) {
                $q->where('tournament_id', $tournament->id);
            })
            ->whereNotNull('assist_player_id')
            ->get();

        foreach ($assistIncidents as $inc) {
            if ($inc->assistPlayer) {
                $pid = $inc->assistPlayer->id;
                if (isset($scorersData[$pid])) {
                    $scorersData[$pid]['assists']++;
                }
            }
        }

        usort($scorersData, function ($a, $b) {
            return $b['goals'] <=> $a['goals'];
        });

        // Fair Play List
        $fairPlay = [];
        foreach ($teams as $t) {
            $fairPlay[$t->id] = [
                'team' => $t,
                'yellow' => 0,
                'red' => 0,
                'score' => 0,
            ];
        }

        foreach ($cardIncidents as $c) {
            if (isset($fairPlay[$c->team_id])) {
                if ($c->type === 'card_yellow') {
                    $fairPlay[$c->team_id]['yellow']++;
                    $fairPlay[$c->team_id]['score'] += 1;
                } else {
                    $fairPlay[$c->team_id]['red']++;
                    $fairPlay[$c->team_id]['score'] += 3;
                }
            }
        }

        usort($fairPlay, function ($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        return view('tournaments.standings', compact(
            'tournament',
            'allTournaments',
            'standings',
            'scorersData',
            'fairPlay',
            'priorityOrder'
        ));
    }

    public function destroy($id)
    {
        $tournament = Tournament::findOrFail($id);
        $wasCurrent = $tournament->is_current;
        $tournament->delete();

        if ($wasCurrent) {
            $next = Tournament::orderBy('id', 'desc')->first();
            if ($next) {
                $next->update(['is_current' => true]);
            }
        }

        return redirect()->route('dashboard')->with('success', 'Torneo eliminado.');
    }
}
