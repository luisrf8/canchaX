<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\MatchIncident;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Notifications\MatchFinishedNotification;
use App\Notifications\MatchScoreUpdatedNotification;
use App\Services\TournamentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MatchController extends Controller
{
    private function notifyMatchSubscribers(GameMatch $match, string $message, bool $isFinish = false)
    {
        $tournament = $match->tournament;
        if (!$tournament) return;

        $recipients = collect();
        if ($tournament->organizer) {
            $recipients->push($tournament->organizer);
        }
        foreach ($tournament->collaborators as $collab) {
            $recipients->push($collab);
        }
        foreach ($tournament->subscribers as $sub) {
            $recipients->push($sub);
        }

        $uniqueRecipients = $recipients->unique('id');

        if ($isFinish) {
            Notification::send($uniqueRecipients, new MatchFinishedNotification($match, $message));
        } else {
            Notification::send($uniqueRecipients, new MatchScoreUpdatedNotification($match, $message));
        }
    }
    public function index($tournamentId = null)
    {
        $tournament = $tournamentId ? Tournament::findOrFail($tournamentId) : Tournament::where('is_current', true)->first();
        if (!$tournament) {
            return redirect()->route('tournaments.wizard');
        }

        $allTournaments = Tournament::orderBy('id', 'desc')->get();
        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'winner'])
            ->where('tournament_id', $tournament->id)
            ->orderBy('id', 'asc')
            ->get();

        return view('matches.index', compact('tournament', 'allTournaments', 'matches'));
    }

    public function liveConsole($id)
    {
        $match = GameMatch::with(['homeTeam.players', 'awayTeam.players', 'tournament.collaborators', 'incidents.player', 'incidents.assistPlayer', 'incidents.team'])
            ->findOrFail($id);

        $tournament = $match->tournament;
        $allTournaments = Tournament::orderBy('id', 'desc')->get();

        if ($match->status === 'scheduled') {
            $match->status = 'live';
            $match->save();
        }

        return view('matches.live', compact('match', 'tournament', 'allTournaments'));
    }

    public function updateScore(Request $request, $id, TournamentEngine $engine)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para editar partidos en este torneo.'], 403);
        }

        $action = $request->input('action');
        $teamSide = $request->input('team_side');
        $playerId = $request->input('player_id');
        $assistId = $request->input('assist_player_id');
        $detail = $request->input('detail', 'Anotación');

        $sport = $match->tournament->sport ?? 'futbol';
        $targetTeamId = $teamSide === 'home' ? $match->home_team_id : $match->away_team_id;

        if ($action === 'goal') {
            if ($teamSide === 'home') {
                $match->home_score++;
            } else {
                $match->away_score++;
            }
            $match->save();

            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $targetTeamId,
                'player_id' => $playerId,
                'assist_player_id' => $assistId,
                'type' => 'goal',
                'minute' => (int) ceil($match->elapsed_seconds / 60),
                'period' => $match->current_period,
                'detail' => $detail,
            ]);
        } elseif ($action === 'penalty_kick') {
            $isGoal = $request->boolean('is_goal', true);
            if ($isGoal) {
                if ($teamSide === 'home') {
                    $match->home_penalties++;
                } else {
                    $match->away_penalties++;
                }
                $match->save();
            }

            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $targetTeamId,
                'player_id' => $playerId,
                'type' => 'penalty_kick',
                'minute' => (int) ceil($match->elapsed_seconds / 60),
                'period' => 'Penales',
                'detail' => $isGoal ? 'Penal Anotado ⚽' : 'Penal Fallado ❌',
            ]);
        } elseif ($action === 'point_tennis') {
            $engine->processTennisPoint($match, $teamSide, $sport);

            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $targetTeamId,
                'player_id' => $playerId,
                'type' => 'point_tennis',
                'minute' => (int) ceil($match->elapsed_seconds / 60),
                'period' => $match->current_period,
                'detail' => $detail,
            ]);
        } elseif ($action === 'ace') {
            $stats = $match->stats ?? [];
            $key = $teamSide === 'home' ? 'aces_home' : 'aces_away';
            $stats[$key] = ($stats[$key] ?? 0) + 1;
            $match->stats = $stats;
            $match->save();

            $engine->processTennisPoint($match, $teamSide, $sport);

            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $targetTeamId,
                'player_id' => $playerId,
                'type' => 'ace',
                'minute' => (int) ceil($match->elapsed_seconds / 60),
                'period' => $match->current_period,
                'detail' => 'Saque directo (Ace)',
            ]);
        } elseif ($action === 'point_basket') {
            $points = (int) $request->input('points', 1);
            if ($teamSide === 'home') {
                $match->home_score += $points;
            } else {
                $match->away_score += $points;
            }
            $match->save();

            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $targetTeamId,
                'player_id' => $playerId,
                'type' => 'point_basket_' . $points,
                'minute' => (int) ceil($match->elapsed_seconds / 60),
                'period' => $match->current_period,
                'detail' => "+{$points} Puntos",
            ]);
        }

        $teamName = $teamSide === 'home' ? ($match->homeTeam->name ?? 'Local') : ($match->awayTeam->name ?? 'Visita');
        $msg = "Anotación en {$match->tournament->name}: {$teamName} en el {$match->current_period} ({$match->home_score} - {$match->away_score})";
        $this->notifyMatchSubscribers($match, $msg);

        return response()->json([
            'success' => true,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'home_penalties' => $match->home_penalties,
            'away_penalties' => $match->away_penalties,
            'home_sets' => $match->home_sets,
            'away_sets' => $match->away_sets,
            'tennis_data' => $match->tennis_data,
            'status' => $match->status,
            'current_period' => $match->current_period,
        ]);
    }

    public function addCard(Request $request, $id)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para editar partidos en este torneo.'], 403);
        }

        $teamSide = $request->input('team_side');
        $playerId = $request->input('player_id');
        $cardType = $request->input('card_type');
        $reason = $request->input('reason', 'Amonestación');

        $targetTeamId = $teamSide === 'home' ? $match->home_team_id : $match->away_team_id;

        $incident = MatchIncident::create([
            'game_match_id' => $match->id,
            'team_id' => $targetTeamId,
            'player_id' => $playerId,
            'type' => $cardType,
            'minute' => (int) ceil($match->elapsed_seconds / 60),
            'period' => $match->current_period,
            'detail' => $reason,
        ]);

        $teamName = $teamSide === 'home' ? ($match->homeTeam->name ?? 'Local') : ($match->awayTeam->name ?? 'Visita');
        $cardLabel = $cardType === 'card_yellow' ? 'Tarjeta Amarilla 🟨' : 'Tarjeta Roja 🟥';
        $msg = "Amonestación en {$match->tournament->name}: {$cardLabel} para {$teamName} ({$reason})";
        $this->notifyMatchSubscribers($match, $msg);

        return response()->json(['success' => true, 'incident' => $incident]);
    }

    public function updateStats(Request $request, $id)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para editar partidos en este torneo.'], 403);
        }

        $stats = $match->stats ?? [];

        if ($request->has('possession_home')) {
            $stats['possession_home'] = (int) $request->input('possession_home');
            $stats['possession_away'] = 100 - $stats['possession_home'];
        }

        foreach (['shots_home', 'shots_away', 'fouls_home', 'fouls_away', 'corners_home', 'corners_away'] as $field) {
            if ($request->has($field)) {
                $stats[$field] = (int) $request->input($field);
            }
        }

        $match->stats = $stats;
        $match->save();

        return response()->json(['success' => true, 'stats' => $stats]);
    }

    public function updateTimer(Request $request, $id)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para controlar el cronómetro en este torneo.'], 403);
        }

        if ($request->has('elapsed_seconds')) {
            $match->elapsed_seconds = (int) $request->input('elapsed_seconds');
        }
        if ($request->has('timer_running')) {
            $match->timer_running = (bool) $request->input('timer_running');
        }
        if ($request->has('current_period')) {
            $match->current_period = $request->input('current_period');
        }
        $match->save();

        return response()->json(['success' => true]);
    }

    public function deleteIncident($id, $incidentId)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para editar partidos en este torneo.'], 403);
        }

        $incident = MatchIncident::where('game_match_id', $id)->findOrFail($incidentId);

        if ($incident->type === 'goal') {
            if ($incident->team_id == $match->home_team_id && $match->home_score > 0) {
                $match->home_score--;
            } elseif ($incident->team_id == $match->away_team_id && $match->away_score > 0) {
                $match->away_score--;
            }
            $match->save();
        } elseif ($incident->type === 'penalty_kick' && str_contains($incident->detail, 'Anotado')) {
            if ($incident->team_id == $match->home_team_id && $match->home_penalties > 0) {
                $match->home_penalties--;
            } elseif ($incident->team_id == $match->away_team_id && $match->away_penalties > 0) {
                $match->away_penalties--;
            }
            $match->save();
        }

        $incident->delete();

        return response()->json(['success' => true]);
    }

    public function finishMatch(Request $request, $id, TournamentEngine $engine)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return back()->withErrors(['auth' => 'No tienes permisos para finalizar partidos en este torneo.']);
        }

        $winnerSide = $request->input('winner');

        if ($winnerSide === 'home') {
            $match->winner_id = $match->home_team_id;
        } elseif ($winnerSide === 'away') {
            $match->winner_id = $match->away_team_id;
        } else {
            if ($match->home_score > $match->away_score) {
                $match->winner_id = $match->home_team_id;
            } elseif ($match->away_score > $match->home_score) {
                $match->winner_id = $match->away_team_id;
            } elseif ($match->home_penalties > $match->away_penalties) {
                $match->winner_id = $match->home_team_id;
            } elseif ($match->away_penalties > $match->home_penalties) {
                $match->winner_id = $match->away_team_id;
            }
        }

        $match->status = 'finished';
        $match->current_period = 'Finalizado';
        $match->timer_running = false;
        $match->save();

        $engine->advanceWinner($match);

        $winnerName = $match->winner->name ?? 'Empate';
        $msg = "Partido Finalizado en {$match->tournament->name}: Marcador Final {$match->home_score} - {$match->away_score}. Ganador: {$winnerName}";
        $this->notifyMatchSubscribers($match, $msg, true);

        return redirect()->route('matches.live', $match->id)->with('success', 'Partido finalizado con éxito.');
    }
}
