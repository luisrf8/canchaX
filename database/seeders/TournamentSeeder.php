<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\MatchIncident;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create sample organizers (if not exist)
        $organizer1 = User::firstOrCreate(
            ['email' => 'carlos@canchax.com'],
            [
                'name' => 'Carlos Mendoza',
                'password' => Hash::make('password'),
                'role' => 'organizer',
                'city' => 'Madrid',
                'bio' => 'Organizador oficial de torneos de fútbol y tenis con más de 8 años de experiencia en ligas locales.',
                'avatar_color' => '#1e3a8a',
            ]
        );

        $organizer2 = User::firstOrCreate(
            ['email' => 'elena@canchax.com'],
            [
                'name' => 'Elena Rostova',
                'password' => Hash::make('password'),
                'role' => 'organizer',
                'city' => 'Barcelona',
                'bio' => 'Directora de competiciones de Pádel y Baloncesto universitario.',
                'avatar_color' => '#0369a1',
            ]
        );

        $participant1 = User::firstOrCreate(
            ['email' => 'mateo@canchax.com'],
            [
                'name' => 'Mateo Fernández',
                'password' => Hash::make('password'),
                'role' => 'participant',
                'city' => 'Madrid',
                'bio' => 'Jugador activo de fútbol y tenis.',
                'avatar_color' => '#d97706',
            ]
        );

        // 2. Set existing current false
        Tournament::query()->update(['is_current' => false]);

        // 3. Main Tournament (Copa de Campeones 2026)
        $tournament = Tournament::create([
            'user_id' => $organizer1->id,
            'name' => 'Copa de Campeones 2026',
            'sport' => 'futbol',
            'format' => 'knockout',
            'status' => 'active',
            'location' => 'Polideportivo Municipal Central - Madrid',
            'description' => 'El torneo anual de fútbol de la capital. 8 clubes disputando la copa de campeones.',
            'is_current' => true,
            'settings' => [
                'period_duration' => 45,
                'periods_count' => 2,
                'scoring_unit' => 'goles',
            ],
        ]);

        $teamsData = [
            ['name' => 'Águilas FC', 'short_name' => 'AGU', 'color' => '#1e3a8a', 'city' => 'Madrid', 'coach' => 'Carlos Tévez'],
            ['name' => 'Fénix Real', 'short_name' => 'FNX', 'color' => '#b91c1c', 'city' => 'Barcelona', 'coach' => 'Xavi H.'],
            ['name' => 'Rayo Azul', 'short_name' => 'RAY', 'color' => '#0369a1', 'city' => 'Valencia', 'coach' => 'Unai E.'],
            ['name' => 'Tigres del Norte', 'short_name' => 'TIG', 'color' => '#d97706', 'city' => 'Sevilla', 'coach' => 'Manuel P.'],
            ['name' => 'Cóndor Blanco', 'short_name' => 'CDN', 'color' => '#475569', 'city' => 'Bilbao', 'coach' => 'Ernesto V.'],
            ['name' => 'Halcones Dorados', 'short_name' => 'HAL', 'color' => '#854d0e', 'city' => 'Zaragoza', 'coach' => 'Marcelino'],
            ['name' => 'Dragones Rojos', 'short_name' => 'DRG', 'color' => '#9f1239', 'city' => 'Vigo', 'coach' => 'Claudio G.'],
            ['name' => 'Leones de la Sierra', 'short_name' => 'LEO', 'color' => '#15803d', 'city' => 'Granada', 'coach' => 'Diego M.'],
        ];

        $teams = [];
        foreach ($teamsData as $data) {
            $team = Team::create(array_merge($data, ['tournament_id' => $tournament->id]));
            $teams[] = $team;

            $players = [
                ['name' => 'Mateo Fernández', 'dorsal' => 1, 'position' => 'Portero'],
                ['name' => 'Lucas Silva', 'dorsal' => 4, 'position' => 'Defensa'],
                ['name' => 'Santiago Gómez', 'dorsal' => 5, 'position' => 'Defensa'],
                ['name' => 'Daniel Ruiz', 'dorsal' => 8, 'position' => 'Mediocampista'],
                ['name' => 'Joaquín Navarro', 'dorsal' => 10, 'position' => 'Mediocampista'],
                ['name' => 'Gabriel Benítez', 'dorsal' => 9, 'position' => 'Delantero'],
            ];

            foreach ($players as $p) {
                Player::create(array_merge($p, ['team_id' => $team->id]));
            }
        }

        // Final & Semis
        $finalMatch = GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Gran Final',
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[2]->id,
            'status' => 'scheduled',
            'scheduled_time' => '18:00',
        ]);

        $semi1 = GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Semifinal',
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'home_score' => 2,
            'away_score' => 1,
            'current_period' => '2T',
            'status' => 'live',
            'elapsed_seconds' => 3240,
            'timer_running' => true,
            'next_match_id' => $finalMatch->id,
            'next_match_slot' => 'home',
            'scheduled_time' => '16:00',
        ]);

        $aguPlayers = $teams[0]->players;
        $fnxPlayers = $teams[1]->players;

        MatchIncident::create([
            'game_match_id' => $semi1->id,
            'team_id' => $teams[0]->id,
            'player_id' => $aguPlayers[5]->id ?? null,
            'assist_player_id' => $aguPlayers[4]->id ?? null,
            'type' => 'goal',
            'minute' => 14,
            'period' => '1T',
            'detail' => 'Gol de Jugada',
        ]);

        MatchIncident::create([
            'game_match_id' => $semi1->id,
            'team_id' => $teams[1]->id,
            'player_id' => $fnxPlayers[5]->id ?? null,
            'type' => 'goal',
            'minute' => 38,
            'period' => '1T',
            'detail' => 'Gol de Penal',
        ]);

        MatchIncident::create([
            'game_match_id' => $semi1->id,
            'team_id' => $teams[0]->id,
            'player_id' => $aguPlayers[4]->id ?? null,
            'type' => 'goal',
            'minute' => 51,
            'period' => '2T',
            'detail' => 'Tiro Libre',
        ]);

        $semi2 = GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Semifinal',
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'home_score' => 0,
            'away_score' => 0,
            'current_period' => '1T',
            'status' => 'scheduled',
            'next_match_id' => $finalMatch->id,
            'next_match_slot' => 'away',
            'scheduled_time' => '17:00',
        ]);

        // Quarterfinals
        GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Cuartos de final',
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[4]->id,
            'home_score' => 3,
            'away_score' => 1,
            'current_period' => 'Finalizado',
            'status' => 'finished',
            'winner_id' => $teams[0]->id,
            'next_match_id' => $semi1->id,
            'next_match_slot' => 'home',
        ]);

        GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Cuartos de final',
            'home_team_id' => $teams[1]->id,
            'away_team_id' => $teams[5]->id,
            'home_score' => 2,
            'away_score' => 0,
            'current_period' => 'Finalizado',
            'status' => 'finished',
            'winner_id' => $teams[1]->id,
            'next_match_id' => $semi1->id,
            'next_match_slot' => 'away',
        ]);

        // 4. Second Tournament (Master Pádel Barcelona 2026 - Elena Rostova)
        $t2 = Tournament::create([
            'user_id' => $organizer2->id,
            'name' => 'Master Pádel Barcelona 2026',
            'sport' => 'padel',
            'format' => 'knockout',
            'status' => 'active',
            'location' => 'Club Pádel Pro Diagonal - Barcelona',
            'description' => 'Torneo oficial de parejas de pádel profesional con regla de Punto de Oro.',
            'is_current' => false,
        ]);

        $padelTeamsData = [
            ['name' => 'Pérez / Galán', 'short_name' => 'PG', 'color' => '#0284c7', 'city' => 'Barcelona'],
            ['name' => 'Coello / Tapia', 'short_name' => 'CT', 'color' => '#e11d48', 'city' => 'Madrid'],
            ['name' => 'Chingotto / Lebrón', 'short_name' => 'CL', 'color' => '#16a34a', 'city' => 'Sevilla'],
            ['name' => 'Di Nenno / Stupa', 'short_name' => 'DS', 'color' => '#ca8a04', 'city' => 'Valencia'],
        ];

        $pTeams = [];
        foreach ($padelTeamsData as $pd) {
            $pTeams[] = Team::create(array_merge($pd, ['tournament_id' => $t2->id]));
        }

        $pFinal = GameMatch::create([
            'tournament_id' => $t2->id,
            'stage' => 'Gran Final',
            'home_team_id' => $pTeams[0]->id,
            'away_team_id' => $pTeams[1]->id,
            'status' => 'scheduled',
        ]);

        GameMatch::create([
            'tournament_id' => $t2->id,
            'stage' => 'Semifinal 1',
            'home_team_id' => $pTeams[0]->id,
            'away_team_id' => $pTeams[1]->id,
            'home_score' => 6,
            'away_score' => 4,
            'home_sets' => 2,
            'away_sets' => 0,
            'current_period' => 'Finalizado',
            'status' => 'finished',
            'winner_id' => $pTeams[0]->id,
            'next_match_id' => $pFinal->id,
            'next_match_slot' => 'home',
        ]);

        // 3. Historical Completed Tournament (Liga de Tenis Invierno 2025 - Carlos Mendoza)
        $t3 = Tournament::create([
            'user_id' => $organizer1->id,
            'name' => 'Liga de Tenis Invierno 2025',
            'sport' => 'tenis',
            'format' => 'league',
            'status' => 'completed',
            'location' => 'Real Club Tenis Chamartín - Madrid',
            'description' => 'Edición invernal finalizada con éxito.',
            'is_current' => false,
        ]);
    }
}
