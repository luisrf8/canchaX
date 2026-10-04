<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealtimeController extends Controller
{
    /**
     * SSE WebSockets Real-time Data Stream
     */
    public function stream(Request $request): StreamedResponse
    {
        $matchId = $request->query('match_id');
        $tournamentId = $request->query('tournament_id');

        $response = new StreamedResponse(function () use ($matchId, $tournamentId) {
            // Set header parameters for SSE streaming
            echo "event: ping\n";
            echo 'data: {"status":"connected"}' . "\n\n";
            @ob_flush();
            @flush();

            $lastMatchVersion = null;
            $lastNotificationId = null;

            // Stream for 25 seconds per connection request (reconnects automatically on client)
            $startTime = time();
            while (time() - $startTime < 25) {

                // 1. Match specific update check
                if ($matchId) {
                    $match = GameMatch::with(['incidents.player', 'incidents.team'])->find($matchId);
                    if ($match) {
                        $currentVersion = md5($match->updated_at . '_' . $match->home_score . '_' . $match->away_score . '_' . $match->current_period . '_' . $match->elapsed_seconds . '_' . $match->incidents->count());
                        if ($currentVersion !== $lastMatchVersion) {
                            $lastMatchVersion = $currentVersion;
                            echo "event: match_updated\n";
                            echo 'data: ' . json_encode([
                                'match_id' => $match->id,
                                'home_score' => $match->home_score,
                                'away_score' => $match->away_score,
                                'home_penalties' => $match->home_penalties,
                                'away_penalties' => $match->away_penalties,
                                'home_sets' => $match->home_sets,
                                'away_sets' => $match->away_sets,
                                'tennis_data' => $match->tennis_data,
                                'current_period' => $match->current_period,
                                'elapsed_seconds' => $match->elapsed_seconds,
                                'timer_running' => (bool) $match->timer_running,
                                'status' => $match->status,
                                'incidents_count' => $match->incidents->count(),
                            ]) . "\n\n";
                            @ob_flush();
                            @flush();
                        }
                    }
                }

                // 2. Global notification check for logged in user
                if (auth()->check()) {
                    $latestNotif = auth()->user()->notifications()->first();
                    if ($latestNotif && $latestNotif->id !== $lastNotificationId) {
                        $lastNotificationId = $latestNotif->id;
                        echo "event: notification_pushed\n";
                        echo 'data: ' . json_encode([
                            'id' => $latestNotif->id,
                            'data' => $latestNotif->data,
                            'read_at' => $latestNotif->read_at,
                            'created_at' => $latestNotif->created_at->diffForHumans(),
                            'unread_count' => auth()->user()->unreadNotifications()->count(),
                        ]) . "\n\n";
                        @ob_flush();
                        @flush();
                    }
                }

                usleep(500000); // 0.5s poll delay for instant real-time response
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Subscribe / Unsubscribe user to a tournament for Push Notifications
     */
    public function toggleSubscription(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Debes iniciar sesión para suscribirte a notificaciones de este torneo.'], 401);
        }

        $request->validate(['tournament_id' => 'required|exists:tournaments,id']);
        $tournamentId = $request->input('tournament_id');
        $userId = auth()->id();

        $sub = TournamentSubscription::where('user_id', $userId)->where('tournament_id', $tournamentId)->first();

        if ($sub) {
            $sub->delete();
            $subscribed = false;
            $msg = 'Has cancelado la suscripción a este torneo.';
        } else {
            TournamentSubscription::create(['user_id' => $userId, 'tournament_id' => $tournamentId]);
            $subscribed = true;
            $msg = '🔔 ¡Suscrito exitosamente! Recibirás notificaciones en tiempo real de este torneo.';
        }

        return response()->json([
            'success' => true,
            'subscribed' => $subscribed,
            'message' => $msg,
        ]);
    }

    /**
     * Mark all notifications as read for current user
     */
    public function markNotificationsRead()
    {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get user notifications
     */
    public function getNotifications()
    {
        if (!auth()->check()) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $notifications = auth()->user()->notifications()->take(10)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => auth()->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }
}
