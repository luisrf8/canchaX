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

        // Sincronizar tiempo informado por el cliente si fue enviado
        if ($request->filled('elapsed_seconds')) {
            $match->setExactSeconds((int) $request->input('elapsed_seconds'));
        }
        $minute = $match->getCurrentMinute();

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
                'minute' => $minute,
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
                'minute' => $minute,
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
                'minute' => $minute,
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
                'minute' => $minute,
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
                'minute' => $minute,
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
            'elapsed_seconds' => $match->getLiveElapsedSeconds(),
            'timer_running' => (bool) $match->timer_running,
        ]);
    }

    public function addCard(Request $request, $id)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para editar partidos en este torneo.'], 403);
        }

        // Sincronizar tiempo informado por el cliente si fue enviado
        if ($request->filled('elapsed_seconds')) {
            $match->setExactSeconds((int) $request->input('elapsed_seconds'));
        }
        $minute = $match->getCurrentMinute();

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
            'minute' => $minute,
            'period' => $match->current_period,
            'detail' => $reason,
        ]);

        $teamName = $teamSide === 'home' ? ($match->homeTeam->name ?? 'Local') : ($match->awayTeam->name ?? 'Visita');
        $cardLabel = $cardType === 'card_yellow' ? 'Tarjeta Amarilla 🟨' : 'Tarjeta Roja 🟥';
        $msg = "Amonestación en {$match->tournament->name}: {$cardLabel} para {$teamName} ({$reason})";
        $this->notifyMatchSubscribers($match, $msg);

        return response()->json([
            'success' => true,
            'incident' => $incident,
            'elapsed_seconds' => $match->getLiveElapsedSeconds(),
            'timer_running' => (bool) $match->timer_running,
        ]);
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

        $clientSeconds = $request->has('elapsed_seconds') ? (int) $request->input('elapsed_seconds') : null;

        if ($request->has('timer_running')) {
            $running = (bool) $request->input('timer_running');
            if ($running && !$match->timer_running) {
                if ($clientSeconds !== null) {
                    $match->elapsed_seconds = $clientSeconds;
                }
                $match->startTimer();
            } elseif (!$running && $match->timer_running) {
                $match->pauseTimer($clientSeconds);
            } elseif ($clientSeconds !== null) {
                $match->setExactSeconds($clientSeconds);
            }
        } elseif ($clientSeconds !== null) {
            $match->setExactSeconds($clientSeconds);
        }

        if ($request->has('current_period')) {
            $match->current_period = $request->input('current_period');
            $match->save();
        }

        return response()->json([
            'success' => true,
            'elapsed_seconds' => $match->getLiveElapsedSeconds(),
            'timer_running' => (bool) $match->timer_running,
            'current_period' => $match->current_period,
        ]);
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

    public function updatePhase(Request $request, $id, TournamentEngine $engine)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return response()->json(['error' => 'No tienes permisos para modificar este partido.'], 403);
        }

        $newPeriod = $request->input('period');
        $actionNote = $request->input('note');
        $startTimer = $request->boolean('start_timer', false);

        if ($request->filled('elapsed_seconds')) {
            $match->setExactSeconds((int) $request->input('elapsed_seconds'));
        }

        // Si se pide finalizar
        if ($newPeriod === 'Finalizado') {
            return $this->finishMatch($request, $id, $engine);
        }

        // Gestión del cronómetro según el periodo
        if (in_array($newPeriod, ['Descanso', 'Descanso Extra'])) {
            $match->pauseTimer();
        } elseif (in_array($newPeriod, ['1T', '2T', '1TE', '2TE'])) {
            if ($startTimer) {
                $match->startTimer();
            }
        } elseif ($newPeriod === 'Penales') {
            $match->pauseTimer();
        }

        $match->current_period = $newPeriod;
        $match->status = 'live';
        $match->save();

        if ($actionNote) {
            MatchIncident::create([
                'game_match_id' => $match->id,
                'team_id' => $match->home_team_id,
                'type' => 'period_change',
                'minute' => $match->getCurrentMinute(),
                'period' => $newPeriod,
                'detail' => $actionNote,
            ]);
        }

        $msg = "Periodo actualizado en {$match->tournament->name}: {$newPeriod} ({$match->home_score} - {$match->away_score})";
        $this->notifyMatchSubscribers($match, $msg);

        return response()->json([
            'success' => true,
            'current_period' => $match->current_period,
            'status' => $match->status,
            'timer_running' => (bool) $match->timer_running,
            'elapsed_seconds' => $match->getLiveElapsedSeconds(),
        ]);
    }

    public function reopenMatch($id)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            return back()->withErrors(['auth' => 'No tienes permisos para reabrir este partido.']);
        }

        $match->status = 'live';
        if ($match->current_period === 'Finalizado') {
            $match->current_period = ($match->home_penalties > 0 || $match->away_penalties > 0) ? 'Penales' : '2T';
        }
        $match->winner_id = null;
        $match->timer_running = false;
        $match->save();

        return redirect()->route('matches.live', $match->id)->with('success', 'Partido reactivado en vivo para arbitraje.');
    }

    public function finishMatch(Request $request, $id, TournamentEngine $engine)
    {
        $match = GameMatch::with(['tournament.collaborators'])->findOrFail($id);
        if (!$match->tournament->canManage(auth()->user())) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            return back()->withErrors(['auth' => 'No tienes permisos para finalizar partidos en este torneo.']);
        }

        if ($request->filled('elapsed_seconds')) {
            $match->setExactSeconds((int) $request->input('elapsed_seconds'));
        }

        $winnerSide = $request->input('winner');

        if ($winnerSide === 'home') {
            $match->winner_id = $match->home_team_id;
        } elseif ($winnerSide === 'away') {
            $match->winner_id = $match->away_team_id;
        } elseif ($winnerSide === 'draw') {
            $match->winner_id = null;
        } else {
            // Detección automática por goles o penales
            if ($match->home_score > $match->away_score) {
                $match->winner_id = $match->home_team_id;
            } elseif ($match->away_score > $match->home_score) {
                $match->winner_id = $match->away_team_id;
            } elseif ($match->home_penalties > $match->away_penalties) {
                $match->winner_id = $match->home_team_id;
            } elseif ($match->away_penalties > $match->home_penalties) {
                $match->winner_id = $match->away_team_id;
            } else {
                $match->winner_id = null;
            }
        }

        $match->elapsed_seconds = $match->getLiveElapsedSeconds();
        $match->status = 'finished';
        $match->current_period = 'Finalizado';
        $match->timer_running = false;
        $match->timer_started_at = null;
        $match->save();

        MatchIncident::create([
            'game_match_id' => $match->id,
            'team_id' => $match->winner_id ?? $match->home_team_id,
            'type' => 'period_change',
            'minute' => $match->getCurrentMinute(),
            'period' => 'Finalizado',
            'detail' => 'Pitazo Final: Partido Concluido Oficialmente',
        ]);

        $engine->advanceWinner($match);

        $winnerName = $match->winner->name ?? ($match->winner_id ? 'Definido' : 'Empate');
        $msg = "Partido Finalizado en {$match->tournament->name}: Marcador Oficial {$match->home_score} - {$match->away_score}. " . ($match->winner_id ? "Ganador: {$winnerName}" : "Resultado: Empate");
        $this->notifyMatchSubscribers($match, $msg, true);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'finished',
                'current_period' => 'Finalizado',
                'winner_id' => $match->winner_id,
                'winner_name' => $winnerName,
            ]);
        }

        return redirect()->route('matches.live', $match->id)->with('success', '¡Partido finalizado con éxito! El estatus ha cambiado a Finalizado.');
    }
}