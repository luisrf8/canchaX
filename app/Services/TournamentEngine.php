<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;

class TournamentEngine
{
    public function generateTournamentStructure(Tournament $tournament, array $teamsInput): void
    {
        $createdTeams = [];
        foreach ($teamsInput as $idx => $t) {
            $createdTeams[] = Team::create([
                'tournament_id' => $tournament->id,
                'name' => $t['name'] ?? ('Equipo ' . ($idx + 1)),
                'short_name' => strtoupper($t['short_name'] ?? ('EQ' . ($idx + 1))),
                'color' => $t['color'] ?? '#1e3a8a',
                'city' => $t['city'] ?? null,
                'coach' => $t['coach'] ?? null,
                'group_name' => $t['group_name'] ?? null,
            ]);
        }

        if ($tournament->format === 'knockout') {
            $this->generateDynamicKnockoutBrackets($tournament, $createdTeams);
        } elseif ($tournament->format === 'league') {
            $this->generateLeagueFixture($tournament, $createdTeams);
        } elseif ($tournament->format === 'groups') {
            $this->generateGroupFixture($tournament, $createdTeams);
        }
    }

    private function generateDynamicKnockoutBrackets(Tournament $tournament, array $teams): void
    {
        $count = count($teams);

        if ($count <= 2) {
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Gran Final',
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id ?? $teams[0]->id,
                'status' => 'scheduled',
            ]);
            return;
        }

        // Determine nearest power of 2 for brackets (4, 8, 16, 32)
        $rounds = 4;
        if ($count > 4 && $count <= 8) $rounds = 8;
        if ($count > 8 && $count <= 16) $rounds = 16;
        if ($count > 16) $rounds = 32;

        // Create Final match
        $final = GameMatch::create([
            'tournament_id' => $tournament->id,
            'stage' => 'Gran Final',
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'status' => 'scheduled',
        ]);

        if ($rounds === 4) {
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Semifinal 1',
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id,
                'status' => 'scheduled',
                'next_match_id' => $final->id,
                'next_match_slot' => 'home',
            ]);

            GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Semifinal 2',
                'home_team_id' => $teams[2]->id ?? $teams[0]->id,
                'away_team_id' => $teams[3]->id ?? $teams[1]->id,
                'status' => 'scheduled',
                'next_match_id' => $final->id,
                'next_match_slot' => 'away',
            ]);
        } else {
            // 8, 16, or dynamic rounds
            $semi1 = GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Semifinal 1',
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id,
                'status' => 'scheduled',
                'next_match_id' => $final->id,
                'next_match_slot' => 'home',
            ]);

            $semi2 = GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Semifinal 2',
                'home_team_id' => $teams[2]->id,
                'away_team_id' => $teams[3]->id,
                'status' => 'scheduled',
                'next_match_id' => $final->id,
                'next_match_slot' => 'away',
            ]);

            // Pairs for Quarterfinals
            $pairsCount = (int) ceil($count / 2);
            for ($i = 0; $i < $pairsCount; $i++) {
                $homeIndex = $i * 2;
                $awayIndex = $homeIndex + 1;

                $targetSemi = ($i % 2 === 0) ? $semi1 : $semi2;
                $slot = ($i < 2) ? 'home' : 'away';

                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $teams[$homeIndex]->id,
                    'away_team_id' => isset($teams[$awayIndex]) ? $teams[$awayIndex]->id : $teams[0]->id,
                    'status' => 'scheduled',
                    'next_match_id' => $targetSemi->id,
                    'next_match_slot' => $slot,
                ]);
            }
        }
    }

    private function generateLeagueFixture(Tournament $tournament, array $teams): void
    {
        $count = count($teams);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Jornada ' . ($i + 1),
                    'home_team_id' => $teams[$i]->id,
                    'away_team_id' => $teams[$j]->id,
                    'status' => 'scheduled',
                ]);
            }
        }
    }

    private function generateGroupFixture(Tournament $tournament, array $teams): void
    {
        $half = (int) ceil(count($teams) / 2);
        $groupA = array_slice($teams, 0, $half);
        $groupB = array_slice($teams, $half);

        foreach ($groupA as $t) {
            $t->update(['group_name' => 'Grupo A']);
        }
        foreach ($groupB as $t) {
            $t->update(['group_name' => 'Grupo B']);
        }

        for ($i = 0; $i < count($groupA); $i++) {
            for ($j = $i + 1; $j < count($groupA); $j++) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Grupo A',
                    'home_team_id' => $groupA[$i]->id,
                    'away_team_id' => $groupA[$j]->id,
                    'status' => 'scheduled',
                ]);
            }
        }

        for ($i = 0; $i < count($groupB); $i++) {
            for ($j = $i + 1; $j < count($groupB); $j++) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Grupo B',
                    'home_team_id' => $groupB[$i]->id,
                    'away_team_id' => $groupB[$j]->id,
                    'status' => 'scheduled',
                ]);
            }
        }

        if (!empty($groupA) && !empty($groupB)) {
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'stage' => 'Gran Final Playoff',
                'home_team_id' => $groupA[0]->id,
                'away_team_id' => $groupB[0]->id,
                'status' => 'scheduled',
            ]);
        }
    }

    public function advanceWinner(GameMatch $match): void
    {
        if (!$match->next_match_id || !$match->winner_id) {
            return;
        }

        $nextMatch = GameMatch::find($match->next_match_id);
        if (!$nextMatch) {
            return;
        }

        if ($match->next_match_slot === 'home') {
            $nextMatch->home_team_id = $match->winner_id;
        } elseif ($match->next_match_slot === 'away') {
            $nextMatch->away_team_id = $match->winner_id;
        }
        $nextMatch->save();
    }

    public function processTennisPoint(GameMatch $match, string $teamSide, string $sport): void
    {
        $tennisData = $match->tennis_data ?? [
            'home_pts' => '0',
            'away_pts' => '0',
            'home_games' => 0,
            'away_games' => 0,
            'home_sets' => 0,
            'away_sets' => 0,
            'is_tiebreak' => false,
            'set_history' => [],
        ];

        $myKey = $teamSide . '_pts';
        $oppKey = ($teamSide === 'home' ? 'away' : 'home') . '_pts';
        $myGamesKey = $teamSide . '_games';
        $oppGamesKey = ($teamSide === 'home' ? 'away' : 'home') . '_games';
        $mySetsKey = $teamSide . '_sets';

        if (!empty($tennisData['is_tiebreak'])) {
            $tennisData[$myKey] = (int) ($tennisData[$myKey] ?? 0) + 1;
            $myPts = (int) $tennisData[$myKey];
            $oppPts = (int) $tennisData[$oppKey];

            if ($myPts >= 7 && ($myPts - $oppPts) >= 2) {
                $tennisData[$myGamesKey] = $tennisData[$myGamesKey] + 1;
                $tennisData['set_history'][] = [
                    'home' => $tennisData['home_games'],
                    'away' => $tennisData['away_games'],
                ];
                $tennisData[$mySetsKey] = $tennisData[$mySetsKey] + 1;
                $tennisData['home_games'] = 0;
                $tennisData['away_games'] = 0;
                $tennisData['home_pts'] = '0';
                $tennisData['away_pts'] = '0';
                $tennisData['is_tiebreak'] = false;
            }
        } else {
            $currMy = (string) ($tennisData[$myKey] ?? '0');
            $currOpp = (string) ($tennisData[$oppKey] ?? '0');

            if ($currMy === '0') {
                $tennisData[$myKey] = '15';
            } elseif ($currMy === '15') {
                $tennisData[$myKey] = '30';
            } elseif ($currMy === '30') {
                $tennisData[$myKey] = '40';
            } elseif ($currMy === '40') {
                if ($currOpp === '40') {
                    if ($sport === 'padel') {
                        $this->winTennisGame($tennisData, $teamSide);
                    } else {
                        $tennisData[$myKey] = 'AD';
                    }
                } elseif ($currOpp === 'AD') {
                    $tennisData[$oppKey] = '40';
                } else {
                    $this->winTennisGame($tennisData, $teamSide);
                }
            } elseif ($currMy === 'AD') {
                $this->winTennisGame($tennisData, $teamSide);
            }
        }

        $match->home_sets = $tennisData['home_sets'];
        $match->away_sets = $tennisData['away_sets'];
        $match->tennis_data = $tennisData;

        if ($match->home_sets >= 2) {
            $match->winner_id = $match->home_team_id;
            $match->status = 'finished';
            $match->current_period = 'Finalizado';
        } elseif ($match->away_sets >= 2) {
            $match->winner_id = $match->away_team_id;
            $match->status = 'finished';
            $match->current_period = 'Finalizado';
        }

        $match->save();

        if ($match->status === 'finished') {
            $this->advanceWinner($match);
        }
    }

    private function winTennisGame(array &$tennisData, string $teamSide): void
    {
        $myGamesKey = $teamSide . '_games';
        $oppGamesKey = ($teamSide === 'home' ? 'away' : 'home') . '_games';
        $mySetsKey = $teamSide . '_sets';

        $tennisData[$myGamesKey] = ($tennisData[$myGamesKey] ?? 0) + 1;
        $tennisData['home_pts'] = '0';
        $tennisData['away_pts'] = '0';

        $myG = $tennisData[$myGamesKey];
        $oppG = $tennisData[$oppGamesKey];

        if ($myG == 6 && $oppG == 6) {
            $tennisData['is_tiebreak'] = true;
            $tennisData['home_pts'] = 0;
            $tennisData['away_pts'] = 0;
        } elseif ($myG >= 6 && ($myG - $oppG) >= 2) {
            $tennisData['set_history'][] = [
                'home' => $tennisData['home_games'],
                'away' => $tennisData['away_games'],
            ];
            $tennisData[$mySetsKey] = ($tennisData[$mySetsKey] ?? 0) + 1;
            $tennisData['home_games'] = 0;
            $tennisData['away_games'] = 0;
        }
    }
}
