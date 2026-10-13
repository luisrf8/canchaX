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
        // Nota: Las llaves de playoffs se generan oficialmente al cerrar la fase de grupos con los clasificados reales.
    }

    /**
     * Compute full sorted standings for a tournament
     */
    public function computeStandings(Tournament $tournament): array
    {
        $teams = Team::where('tournament_id', $tournament->id)->get();
        $matches = GameMatch::where('tournament_id', $tournament->id)->get();

        $finishedMatches = $matches->filter(function ($m) {
            return $m->status === 'finished' || $m->current_period === 'Finalizado';
        });

        $headToHead = [];
        foreach ($finishedMatches as $m) {
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
                'fair_play' => 0,
            ];
        }

        foreach ($finishedMatches as $m) {
            if (isset($standings[$m->home_team_id]) && isset($standings[$m->away_team_id])) {
                $h = &$standings[$m->home_team_id];
                $a = &$standings[$m->away_team_id];

                $h['pj']++;
                $a['pj']++;
                $h['gf'] += (int) $m->home_score;
                $h['gc'] += (int) $m->away_score;
                $a['gf'] += (int) $m->away_score;
                $a['gc'] += (int) $m->home_score;

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
                    if ($a['gc'] !== $b['gc']) return $a['gc'] <=> $b['gc'];
                }
            }
            return $b['dg'] <=> $a['dg'];
        });

        return $standings;
    }

    /**
     * Get Standings Grouped by Group Name
     */
    public function getGroupStandings(Tournament $tournament): array
    {
        $standings = $this->computeStandings($tournament);
        $groups = [];
        foreach ($standings as $row) {
            $g = $row['team']->group_name ?: 'Grupo A';
            $groups[$g][] = $row;
        }
        ksort($groups);
        return $groups;
    }

    /**
     * Get Best 3rd Place Teams (Mejores Terceros de Grupos)
     */
    public function getBestThirdPlaceTeams(Tournament $tournament, ?array $groupStandings = null): array
    {
        if ($groupStandings === null) {
            $groupStandings = $this->getGroupStandings($tournament);
        }

        $thirds = [];
        foreach ($groupStandings as $groupName => $rows) {
            if (isset($rows[2])) { // Tercer lugar del grupo (índice 2)
                $item = $rows[2];
                $item['group_name'] = $groupName;
                $thirds[] = $item;
            }
        }

        // Ordenar mejores terceros por Puntos > DG > GF
        usort($thirds, function ($a, $b) {
            if ($b['pts'] !== $a['pts']) return $b['pts'] <=> $a['pts'];
            if ($b['dg'] !== $a['dg']) return $b['dg'] <=> $a['dg'];
            if ($b['gf'] !== $a['gf']) return $b['gf'] <=> $a['gf'];
            return $a['fair_play'] <=> $b['fair_play'];
        });

        return $thirds;
    }

    /**
     * Get Real-Life Playoff Options Available for the Tournament
     */
    public function getPlayoffStructureOptions(Tournament $tournament): array
    {
        $groupStandings = $this->getGroupStandings($tournament);
        $groupCount = count($groupStandings);
        $options = [];

        if ($groupCount === 2) {
            $options[] = [
                'key' => 'semis_4_cruzados',
                'label' => 'Semifinales Cruzadas (1° vs 2° - 4 Equipos)',
                'description' => '1° Grupo A vs 2° Grupo B y 1° Grupo B vs 2° Grupo A → Gran Final',
                'team_count' => 4,
                'is_recommended' => true,
            ];
            $options[] = [
                'key' => 'final_2_directa',
                'label' => 'Gran Final Directa (Líderes de Grupo - 2 Equipos)',
                'description' => '1° Grupo A vs 1° Grupo B (Partido Único de Campeonato)',
                'team_count' => 2,
                'is_recommended' => false,
            ];
            $options[] = [
                'key' => 'cuartos_8_amplio_2grupos',
                'label' => 'Cuartos de Final (Top 4 de cada Grupo - 8 Equipos)',
                'description' => '1A vs 4B, 2B vs 3A, 1B vs 4A, 2A vs 3B → Semifinales → Gran Final',
                'team_count' => 8,
                'is_recommended' => false,
            ];
        } elseif ($groupCount === 3) {
            $options[] = [
                'key' => 'cuartos_8_mejores_3ros',
                'label' => 'Cuartos de Final con Mejores 3ros (6 directos + 2 mejores 3ros)',
                'description' => '1° y 2° de Grupos A, B, C + los 2 mejores terceros de la fase regular',
                'team_count' => 8,
                'is_recommended' => true,
            ];
            $options[] = [
                'key' => 'semis_4_lideres',
                'label' => 'Semifinales (3 Líderes + Mejor 2do - 4 Equipos)',
                'description' => 'Los 3 primeros de grupo y el mejor segundo lugar avanzan a Semifinales',
                'team_count' => 4,
                'is_recommended' => false,
            ];
        } elseif ($groupCount >= 4) {
            $options[] = [
                'key' => 'cuartos_8_cruzados',
                'label' => 'Cuartos de Final (1° vs 2° Cruzado - 8 Equipos)',
                'description' => '1A vs 2B, 1C vs 2D, 1B vs 2A, 1D vs 2C → Semifinales → Gran Final',
                'team_count' => 8,
                'is_recommended' => true,
            ];
            $options[] = [
                'key' => 'semis_4_lideres',
                'label' => 'Semifinales (Líderes de cada Grupo - 4 Equipos)',
                'description' => '1A vs 1B y 1C vs 1D → Gran Final',
                'team_count' => 4,
                'is_recommended' => false,
            ];
        } else {
            // 1 Grupo / Liga
            $options[] = [
                'key' => 'liga_top4',
                'label' => 'Playoffs Semifinales (1° vs 4°, 2° vs 3° - 4 Equipos)',
                'description' => 'Los 4 mejores de la tabla regular disputan Semifinales y Gran Final',
                'team_count' => 4,
                'is_recommended' => true,
            ];
            $options[] = [
                'key' => 'liga_top2',
                'label' => 'Gran Final Directa (1° vs 2° - 2 Equipos)',
                'description' => 'Los 2 líderes disputan la Gran Final',
                'team_count' => 2,
                'is_recommended' => false,
            ];
        }

        return $options;
    }

    /**
     * Generate Official Playoff Bracket from Group Stage Standings
     */
    public function generatePlayoffBracket(Tournament $tournament, string $formatKey = 'auto'): array
    {
        $groupStandings = $this->getGroupStandings($tournament);
        $groupCount = count($groupStandings);
        $bestThirds = $this->getBestThirdPlaceTeams($tournament, $groupStandings);

        // Eliminar llaves de playoffs previas que no hayan sido jugadas
        GameMatch::where('tournament_id', $tournament->id)
            ->whereIn('stage', [
                'Gran Final', 'Gran Final Playoff', 'Final',
                'Semifinal 1', 'Semifinal 2', 'Semifinal', 'Semifinales',
                'Cuartos de final 1', 'Cuartos de final 2', 'Cuartos de final 3', 'Cuartos de final 4', 'Cuartos de final',
                'Octavos de final'
            ])
            ->delete();

        // Autoselección según la cantidad de grupos
        if ($formatKey === 'auto') {
            if ($groupCount === 2) {
                $formatKey = 'semis_4_cruzados';
            } elseif ($groupCount === 3) {
                $formatKey = 'cuartos_8_mejores_3ros';
            } elseif ($groupCount >= 4) {
                $formatKey = 'cuartos_8_cruzados';
            } else {
                $formatKey = 'liga_top4';
            }
        }

        $createdMatches = [];
        $groups = array_values($groupStandings);

        if ($formatKey === 'semis_4_cruzados') {
            // 1A vs 2B y 1B vs 2A -> Final
            $t1A = $groups[0][0]['team'] ?? null;
            $t2A = $groups[0][1]['team'] ?? $t1A;
            $t1B = isset($groups[1]) ? ($groups[1][0]['team'] ?? null) : $t1A;
            $t2B = isset($groups[1]) ? ($groups[1][1]['team'] ?? $t1B) : $t2A;

            if ($t1A && $t1B) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1B->id,
                    'status' => 'scheduled',
                ]);

                $semi1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 1',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t2B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'home',
                ]);

                $semi2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 2',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t2A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'away',
                ]);

                $createdMatches = [$semi1, $semi2, $final];
            }
        } elseif ($formatKey === 'final_2_directa' || $formatKey === 'liga_top2') {
            $t1 = $groups[0][0]['team'] ?? null;
            $t2 = isset($groups[1]) ? ($groups[1][0]['team'] ?? null) : ($groups[0][1]['team'] ?? null);

            if ($t1 && $t2) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1->id,
                    'away_team_id' => $t2->id,
                    'status' => 'scheduled',
                ]);
                $createdMatches = [$final];
            }
        } elseif ($formatKey === 'cuartos_8_cruzados') {
            $gA = $groups[0] ?? [];
            $gB = $groups[1] ?? [];
            $gC = $groups[2] ?? [];
            $gD = $groups[3] ?? [];

            $t1A = $gA[0]['team'] ?? null;
            $t2A = $gA[1]['team'] ?? $t1A;
            $t1B = $gB[0]['team'] ?? null;
            $t2B = $gB[1]['team'] ?? $t1B;
            $t1C = $gC[0]['team'] ?? $t1A;
            $t2C = $gC[1]['team'] ?? $t2A;
            $t1D = $gD[0]['team'] ?? $t1B;
            $t2D = $gD[1]['team'] ?? $t2B;

            if ($t1A && $t1B) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1B->id,
                    'status' => 'scheduled',
                ]);

                $semi1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 1',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1C->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'home',
                ]);

                $semi2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 2',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t1D->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'away',
                ]);

                $qf1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t2B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'home',
                ]);
                $qf2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1C->id,
                    'away_team_id' => $t2D->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'away',
                ]);
                $qf3 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t2A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'home',
                ]);
                $qf4 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1D->id,
                    'away_team_id' => $t2C->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'away',
                ]);

                $createdMatches = [$qf1, $qf2, $qf3, $qf4, $semi1, $semi2, $final];
            }
        } elseif ($formatKey === 'cuartos_8_mejores_3ros') {
            $gA = $groups[0] ?? [];
            $gB = $groups[1] ?? [];
            $gC = $groups[2] ?? [];

            $t1A = $gA[0]['team'] ?? null;
            $t2A = $gA[1]['team'] ?? null;
            $t1B = $gB[0]['team'] ?? null;
            $t2B = $gB[1]['team'] ?? null;
            $t1C = $gC[0]['team'] ?? null;
            $t2C = $gC[1]['team'] ?? null;

            $best3_1 = $bestThirds[0]['team'] ?? ($gA[2]['team'] ?? $t2A);
            $best3_2 = $bestThirds[1]['team'] ?? ($gB[2]['team'] ?? $t2B);

            if ($t1A && $t1B && $t1C) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1B->id,
                    'status' => 'scheduled',
                ]);

                $semi1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 1',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1C->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'home',
                ]);

                $semi2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 2',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t2A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'away',
                ]);

                $qf1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $best3_2->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'home',
                ]);
                $qf2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t2A->id,
                    'away_team_id' => $t2B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'away',
                ]);
                $qf3 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t2C->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'home',
                ]);
                $qf4 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1C->id,
                    'away_team_id' => $best3_1->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'away',
                ]);

                $createdMatches = [$qf1, $qf2, $qf3, $qf4, $semi1, $semi2, $final];
            }
        } elseif ($formatKey === 'cuartos_8_amplio_2grupos') {
            $gA = $groups[0] ?? [];
            $gB = $groups[1] ?? [];

            $t1A = $gA[0]['team'] ?? null;
            $t2A = $gA[1]['team'] ?? null;
            $t3A = $gA[2]['team'] ?? null;
            $t4A = $gA[3]['team'] ?? null;

            $t1B = $gB[0]['team'] ?? null;
            $t2B = $gB[1]['team'] ?? null;
            $t3B = $gB[2]['team'] ?? null;
            $t4B = $gB[3]['team'] ?? null;

            if ($t1A && $t1B) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t1B->id,
                    'status' => 'scheduled',
                ]);

                $semi1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 1',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t2B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'home',
                ]);

                $semi2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 2',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t2A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'away',
                ]);

                $qf1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1A->id,
                    'away_team_id' => $t4B->id ?? $t2B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'home',
                ]);
                $qf2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t2B->id,
                    'away_team_id' => $t3A->id ?? $t1A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi1->id,
                    'next_match_slot' => 'away',
                ]);
                $qf3 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t1B->id,
                    'away_team_id' => $t4A->id ?? $t2A->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'home',
                ]);
                $qf4 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Cuartos de final',
                    'home_team_id' => $t2A->id,
                    'away_team_id' => $t3B->id ?? $t1B->id,
                    'status' => 'scheduled',
                    'next_match_id' => $semi2->id,
                    'next_match_slot' => 'away',
                ]);

                $createdMatches = [$qf1, $qf2, $qf3, $qf4, $semi1, $semi2, $final];
            }
        } elseif ($formatKey === 'liga_top4') {
            $standings = $this->computeStandings($tournament);
            $t1 = $standings[0]['team'] ?? null;
            $t2 = $standings[1]['team'] ?? null;
            $t3 = $standings[2]['team'] ?? null;
            $t4 = $standings[3]['team'] ?? null;

            if ($t1 && $t2) {
                $final = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Gran Final',
                    'home_team_id' => $t1->id,
                    'away_team_id' => $t2->id,
                    'status' => 'scheduled',
                ]);

                $semi1 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 1',
                    'home_team_id' => $t1->id,
                    'away_team_id' => $t4 ? $t4->id : $t2->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'home',
                ]);

                $semi2 = GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'stage' => 'Semifinal 2',
                    'home_team_id' => $t2->id,
                    'away_team_id' => $t3 ? $t3->id : $t1->id,
                    'status' => 'scheduled',
                    'next_match_id' => $final->id,
                    'next_match_slot' => 'away',
                ]);

                $createdMatches = [$semi1, $semi2, $final];
            }
        }

        // Registrar en configuración del torneo que la fase de grupos está cerrada
        $settings = $tournament->settings ?? [];
        $settings['group_stage_closed'] = true;
        $settings['playoff_format'] = $formatKey;
        $settings['playoffs_closed_at'] = now()->toDateTimeString();
        $tournament->settings = $settings;
        $tournament->save();

        return $createdMatches;
    }

    /**
     * Reopen group stage and clear unplayed playoff matches
     */
    public function reopenGroupStage(Tournament $tournament): void
    {
        GameMatch::where('tournament_id', $tournament->id)
            ->whereIn('stage', [
                'Gran Final', 'Gran Final Playoff', 'Final',
                'Semifinal 1', 'Semifinal 2', 'Semifinal', 'Semifinales',
                'Cuartos de final 1', 'Cuartos de final 2', 'Cuartos de final 3', 'Cuartos de final 4', 'Cuartos de final',
                'Octavos de final'
            ])
            ->where('status', 'scheduled')
            ->delete();

        $settings = $tournament->settings ?? [];
        $settings['group_stage_closed'] = false;
        $tournament->settings = $settings;
        $tournament->save();
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
