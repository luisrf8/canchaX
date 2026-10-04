@extends('layouts.app')

@section('content')
@php
    $sport = $tournament->sport ?? 'futbol';
    $isTennis = in_array($sport, ['tenis', 'padel']);
    $isBasket = $sport === 'baloncesto';
    $settings = $tournament->settings ?? [];
    $periodDuration = $settings['period_duration'] ?? ($sport === 'fifa' ? 6 : 45);
    $extraDuration = $settings['extra_period_duration'] ?? ($sport === 'fifa' ? 3 : 15);
    
    // Stage Specific Rule Lookup
    $stageName = $match->stage;
    $stageRules = $settings['stage_rules'] ?? [];
    $stageKey = 'cuartos';
    if (str_contains(strtolower($stageName), 'final') && !str_contains(strtolower($stageName), 'cuartos') && !str_contains(strtolower($stageName), 'semi')) {
        $stageKey = 'final';
    } elseif (str_contains(strtolower($stageName), 'semi')) {
        $stageKey = 'semis';
    } elseif (str_contains(strtolower($stageName), 'octavo') || str_contains(strtolower($stageName), '8vo')) {
        $stageKey = 'octavos';
    } elseif (str_contains(strtolower($stageName), '16')) {
        $stageKey = '16vos';
    }

    $activeStageRule = $stageRules[$stageKey] ?? [
        'tiebreak' => $settings['knockout_tiebreak'] ?? 'prorroga_penales',
        'leg_type' => 'single'
    ];

    $tiebreakLabel = ($activeStageRule['tiebreak'] ?? 'prorroga_penales') === 'penales_directo' 
        ? 'Penales Directos (Sin Prórroga)' 
        : 'Prórroga (' . $extraDuration . 'm) + Penales';
        
    $legLabel = ($activeStageRule['leg_type'] ?? 'single') === 'double' ? 'Ida y Vuelta' : 'Partido Único';

    $tennisData = $match->tennis_data ?? ['home_pts' => '0', 'away_pts' => '0', 'home_games' => 0, 'away_games' => 0, 'home_sets' => 0, 'away_sets' => 0];
    $isOrganizer = $tournament->canManage(auth()->user());
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{
    elapsedSeconds: {{ $match->elapsed_seconds }},
    timerRunning: {{ $match->timer_running ? 'true' : 'false' }},
    period: '{{ $match->current_period }}',
    homeScore: {{ $match->home_score }},
    awayScore: {{ $match->away_score }},
    homePenalties: {{ $match->home_penalties }},
    awayPenalties: {{ $match->away_penalties }},
    homePts: '{{ $tennisData['home_pts'] ?? '0' }}',
    awayPts: '{{ $tennisData['away_pts'] ?? '0' }}',
    homeGames: {{ $tennisData['home_games'] ?? 0 }},
    awayGames: {{ $tennisData['away_games'] ?? 0 }},
    homeSets: {{ $match->home_sets }},
    awaySets: {{ $match->away_sets }},

    // Modals
    goalModal: false,
    cardModal: false,
    finishModal: false,
    activeTeamSide: 'home',
    selectedPlayerId: '',
    selectedAssistId: '',
    detail: 'Jugada',
    cardType: 'card_yellow',

    init() {
        if (this.timerRunning) {
            this.startTimerInterval();
        }
        this.connectLiveMatchSSE();
    },
    connectLiveMatchSSE() {
        if (!window.EventSource) return;
        const source = new EventSource('/api/realtime/stream?match_id={{ $match->id }}');
        let lastIncidents = {{ $match->incidents->count() }};

        source.addEventListener('match_updated', (e) => {
            const data = JSON.parse(e.data);
            this.homeScore = data.home_score;
            this.awayScore = data.away_score;
            this.homePenalties = data.home_penalties;
            this.awayPenalties = data.away_penalties;
            this.homeSets = data.home_sets;
            this.awaySets = data.away_sets;
            this.period = data.current_period;

            @if(!$isOrganizer)
                this.elapsedSeconds = data.elapsed_seconds;
                this.timerRunning = data.timer_running;
                if (this.timerRunning) {
                    this.startTimerInterval();
                }
            @endif

            if (data.tennis_data) {
                this.homePts = data.tennis_data.home_pts ?? '0';
                this.awayPts = data.tennis_data.away_pts ?? '0';
                this.homeGames = data.tennis_data.home_games ?? 0;
                this.awayGames = data.tennis_data.away_games ?? 0;
            }

            if (data.incidents_count !== lastIncidents) {
                lastIncidents = data.incidents_count;
                window.location.reload();
            }
        });
    },
    formatTime(seconds) {
        let mins = Math.floor(seconds / 60);
        let secs = seconds % 60;
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    },
    toggleTimer() {
        @if($isOrganizer)
            this.timerRunning = !this.timerRunning;
            if (this.timerRunning) {
                this.startTimerInterval();
            }
            this.syncTimerServer();
        @endif
    },
    startTimerInterval() {
        if (window.matchTimerInterval) clearInterval(window.matchTimerInterval);
        window.matchTimerInterval = setInterval(() => {
            if (this.timerRunning) {
                this.elapsedSeconds++;
            }
        }, 1000);
    },
    addSeconds(sec) {
        @if($isOrganizer)
            this.elapsedSeconds = Math.max(0, this.elapsedSeconds + sec);
            this.syncTimerServer();
        @endif
    },
    updatePeriod(newPeriod) {
        @if($isOrganizer)
            this.period = newPeriod;
            this.syncTimerServer();
        @endif
    },
    syncTimerServer() {
        fetch('{{ route('matches.timer', $match->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                elapsed_seconds: this.elapsedSeconds,
                timer_running: this.timerRunning,
                current_period: this.period
            })
        });
    },
    openGoalModal(side) {
        this.activeTeamSide = side;
        this.selectedPlayerId = '';
        this.selectedAssistId = '';
        this.goalModal = true;
    },
    openCardModal(side) {
        this.activeTeamSide = side;
        this.selectedPlayerId = '';
        this.cardModal = true;
    },
    submitScore(action, points = 1, isGoal = true) {
        fetch('{{ route('matches.score', $match->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                action: action,
                team_side: this.activeTeamSide,
                player_id: this.selectedPlayerId,
                assist_player_id: this.selectedAssistId,
                detail: this.detail,
                points: points,
                is_goal: isGoal
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.homeScore = data.home_score;
                this.awayScore = data.away_score;
                this.homePenalties = data.home_penalties;
                this.awayPenalties = data.away_penalties;
                this.homeSets = data.home_sets;
                this.awaySets = data.away_sets;
                if (data.tennis_data) {
                    this.homePts = data.tennis_data.home_pts ?? '0';
                    this.awayPts = data.tennis_data.away_pts ?? '0';
                    this.homeGames = data.tennis_data.home_games ?? 0;
                    this.awayGames = data.tennis_data.away_games ?? 0;
                }
                this.goalModal = false;
                window.location.reload();
            }
        });
    },
    submitCard() {
        fetch('{{ route('matches.card', $match->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                team_side: this.activeTeamSide,
                player_id: this.selectedPlayerId,
                card_type: this.cardType,
                reason: this.detail
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.cardModal = false;
                window.location.reload();
            }
        });
    },
    deleteIncident(incidentId) {
        if (!confirm('¿Eliminar esta incidencia?')) return;
        fetch('/matches/live/{{ $match->id }}/incident/' + incidentId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}">

    <!-- Top Status Bar -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('matches.index') }}" class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold">
                &larr; Volver
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">{{ $match->stage }} &bull; {{ strtoupper($sport) }}</span>
                    <span class="bg-slate-900 text-amber-400 text-[10px] font-bold px-2 py-0.5 rounded font-mono">
                        ⚙️ Regla Ronda: {{ $legLabel }} &bull; {{ $tiebreakLabel }}
                    </span>
                </div>
                <h1 class="text-lg font-extrabold text-slate-900">Mesa de Control y Transmisión</h1>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if($isOrganizer)
                <button @click="finishModal = true" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    Finalizar Partido
                </button>
            @else
                <span class="bg-blue-50 text-blue-900 font-bold text-xs px-3 py-1.5 rounded-lg border border-blue-200">
                    👁️ Modo Lectura Pública
                </span>
            @endif
        </div>
    </div>

    <!-- MAIN DIGITAL SCOREBOARD & STADIUM MONITOR -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 border border-slate-800 shadow-2xl relative overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            
            <!-- Home Team Score Column -->
            <div class="flex flex-col items-center md:items-end space-y-2 text-center md:text-right">
                <div class="flex items-center space-x-3">
                    <span class="w-4 h-4 rounded-full" style="background-color: {{ $match->homeTeam->color ?? '#94a3b8' }}"></span>
                    <span class="text-xs font-bold uppercase text-slate-400 font-mono">{{ $match->homeTeam->short_name }}</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white">{{ $match->homeTeam->name }}</h2>
                <span class="text-xs text-slate-400 font-medium">DT: {{ $match->homeTeam->coach ?? 'N/A' }}</span>
                
                <div class="pt-2 flex items-center space-x-3">
                    @if($isTennis)
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Games / Sets</span>
                            <span class="text-lg font-mono font-bold text-amber-400" x-text="homeGames + ' Games (Set ' + homeSets + ')'"></span>
                        </div>
                    @endif

                    <div class="text-5xl sm:text-7xl font-mono font-black tabular-nums text-white bg-slate-800/80 px-6 py-2 rounded-xl border border-slate-700">
                        <span x-text="{{ $isTennis ? 'homePts' : 'homeScore' }}"></span>
                    </div>
                </div>

                <!-- Penalties Score Badge -->
                <template x-if="period === 'Penales' || homePenalties > 0 || awayPenalties > 0">
                    <div class="bg-amber-500/20 border border-amber-500/50 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-lg font-mono">
                        Penales: <span x-text="homePenalties"></span>
                    </div>
                </template>
            </div>

            <!-- Central Stopwatch & Period Controls -->
            <div class="flex flex-col items-center space-y-4 py-4 md:py-0 border-y md:border-y-0 md:border-x border-slate-800 px-4">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    @if($isOrganizer)
                        <select x-model="period" @change="updatePeriod($el.value)" class="bg-slate-800 text-white font-mono text-xs font-bold px-3 py-1 rounded border border-slate-700">
                            <option value="1T">1er Tiempo (1T - {{ $periodDuration }}m)</option>
                            <option value="Descanso">Descanso</option>
                            <option value="2T">2do Tiempo (2T - {{ $periodDuration }}m)</option>
                            <option value="1TE">1er Tiempo Extra ({{ $extraDuration }}m)</option>
                            <option value="2TE">2do Tiempo Extra ({{ $extraDuration }}m)</option>
                            <option value="Penales">Tanda de Penales ⚽</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    @else
                        <div class="inline-flex items-center space-x-1.5 bg-slate-800/90 text-slate-300 font-mono text-xs font-bold px-3 py-1 rounded-lg border border-slate-700 cursor-not-allowed shadow-inner" title="Solo el creador u organizador autorizado de este torneo puede modificar el periodo">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span x-text="period"></span>
                            <span class="text-[10px] text-slate-400 font-sans font-semibold ml-1">(Bloqueado)</span>
                        </div>
                    @endif
                </div>

                <!-- Stopwatch Large Display -->
                <div class="text-4xl sm:text-5xl font-mono font-black text-amber-400 tracking-wider tabular-nums">
                    <span x-text="formatTime(elapsedSeconds)"></span>
                </div>

                <!-- Timer Action Buttons (Only for Organizer) -->
                @if($isOrganizer)
                    <div class="flex items-center space-x-2">
                        <button @click="toggleTimer()" :class="timerRunning ? 'bg-amber-500 hover:bg-amber-600 text-slate-950' : 'bg-emerald-600 hover:bg-emerald-700 text-white'" class="px-4 py-1.5 rounded-lg text-xs font-extrabold transition">
                            <span x-text="timerRunning ? 'Pausar' : 'Iniciar Tiempo'"></span>
                        </button>
                        <button @click="addSeconds(60)" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg border border-slate-700">+1m</button>
                        <button @click="addSeconds(300)" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg border border-slate-700">+5m</button>
                    </div>
                @endif
            </div>

            <!-- Away Team Score Column -->
            <div class="flex flex-col items-center md:items-start space-y-2 text-center md:text-left">
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold uppercase text-slate-400 font-mono">{{ $match->awayTeam->short_name }}</span>
                    <span class="w-4 h-4 rounded-full" style="background-color: {{ $match->awayTeam->color ?? '#94a3b8' }}"></span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white">{{ $match->awayTeam->name }}</h2>
                <span class="text-xs text-slate-400 font-medium">DT: {{ $match->awayTeam->coach ?? 'N/A' }}</span>
                
                <div class="pt-2 flex items-center space-x-3">
                    <div class="text-5xl sm:text-7xl font-mono font-black tabular-nums text-white bg-slate-800/80 px-6 py-2 rounded-xl border border-slate-700">
                        <span x-text="{{ $isTennis ? 'awayPts' : 'awayScore' }}"></span>
                    </div>
                    @if($isTennis)
                        <div class="text-left">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Games / Sets</span>
                            <span class="text-lg font-mono font-bold text-amber-400" x-text="awayGames + ' Games (Set ' + awaySets + ')'"></span>
                        </div>
                    @endif
                </div>

                <!-- Penalties Score Badge -->
                <template x-if="period === 'Penales' || homePenalties > 0 || awayPenalties > 0">
                    <div class="bg-amber-500/20 border border-amber-500/50 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-lg font-mono">
                        Penales: <span x-text="awayPenalties"></span>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- PENALTY SHOOTOUT CONTROL PANEL (If period === 'Penales') -->
    <div x-show="period === 'Penales'" class="bg-amber-50 rounded-2xl border-2 border-amber-400 p-6 space-y-4 shadow-md">
        <div class="flex items-center justify-between border-b border-amber-200 pb-3">
            <h3 class="text-base font-extrabold text-amber-950 flex items-center space-x-2">
                <span>⚽ Tanda de Penaltis Oficial</span>
            </h3>
            <span class="text-xs font-bold text-amber-900 bg-amber-200 px-3 py-1 rounded-lg font-mono">
                Marcador Penales: <span x-text="homePenalties"></span> - <span x-text="awayPenalties"></span>
            </span>
        </div>

        @if($isOrganizer)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Home Team Penalty Controls -->
                <div class="bg-white p-4 rounded-xl border border-amber-200 space-y-3">
                    <span class="font-bold text-xs text-slate-900 block" x-text="'Penal ' + '{{ $match->homeTeam->name }}'"></span>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="activeTeamSide = 'home'; submitScore('penalty_kick', 1, true)" class="py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs">
                            + Penal Anotado ⚽
                        </button>
                        <button @click="activeTeamSide = 'home'; submitScore('penalty_kick', 0, false)" class="py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs">
                            Penal Fallado ❌
                        </button>
                    </div>
                </div>

                <!-- Away Team Penalty Controls -->
                <div class="bg-white p-4 rounded-xl border border-amber-200 space-y-3">
                    <span class="font-bold text-xs text-slate-900 block" x-text="'Penal ' + '{{ $match->awayTeam->name }}'"></span>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="activeTeamSide = 'away'; submitScore('penalty_kick', 1, true)" class="py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs">
                            + Penal Anotado ⚽
                        </button>
                        <button @click="activeTeamSide = 'away'; submitScore('penalty_kick', 0, false)" class="py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs">
                            Penal Fallado ❌
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- SPORT CONTROL ACTION BUTTONS (Organizers Only) -->
    @if($isOrganizer)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Home Team Actions -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4 shadow-xs">
                <h3 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $match->homeTeam->color }}"></span>
                    <span>Controles: {{ $match->homeTeam->name }}</span>
                </h3>

                @if($isTennis)
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="activeTeamSide = 'home'; submitScore('point_tennis')" class="py-3 bg-blue-900 hover:bg-blue-950 text-white rounded-xl font-bold text-xs shadow-xs transition">
                            + Punto Tenis
                        </button>
                        <button @click="activeTeamSide = 'home'; submitScore('ace')" class="py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-xs transition">
                            + Ace (Saque Directo)
                        </button>
                    </div>
                @elseif($isBasket)
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="activeTeamSide = 'home'; submitScore('point_basket', 1)" class="py-2.5 bg-slate-900 text-white rounded-lg font-bold text-xs">+1 TL</button>
                        <button @click="activeTeamSide = 'home'; submitScore('point_basket', 2)" class="py-2.5 bg-blue-900 text-white rounded-lg font-bold text-xs">+2 Doble</button>
                        <button @click="activeTeamSide = 'home'; submitScore('point_basket', 3)" class="py-2.5 bg-blue-950 text-white rounded-lg font-bold text-xs">+3 Triple</button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="openGoalModal('home')" class="py-3 bg-blue-900 hover:bg-blue-950 text-white rounded-xl font-bold text-xs shadow-xs transition flex items-center justify-center space-x-2">
                            <span>+ Registrar Gol</span>
                        </button>
                        <button @click="openCardModal('home')" class="py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-xs transition flex items-center justify-center space-x-2">
                            <span>+ Tarjeta / Amonestación</span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Away Team Actions -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4 shadow-xs">
                <h3 class="text-sm font-extrabold text-slate-900 border-b border-slate-100 pb-2 flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $match->awayTeam->color }}"></span>
                    <span>Controles: {{ $match->awayTeam->name }}</span>
                </h3>

                @if($isTennis)
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="activeTeamSide = 'away'; submitScore('point_tennis')" class="py-3 bg-blue-900 hover:bg-blue-950 text-white rounded-xl font-bold text-xs shadow-xs transition">
                            + Punto Tenis
                        </button>
                        <button @click="activeTeamSide = 'away'; submitScore('ace')" class="py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-xs transition">
                            + Ace (Saque Directo)
                        </button>
                    </div>
                @elseif($isBasket)
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="activeTeamSide = 'away'; submitScore('point_basket', 1)" class="py-2.5 bg-slate-900 text-white rounded-lg font-bold text-xs">+1 TL</button>
                        <button @click="activeTeamSide = 'away'; submitScore('point_basket', 2)" class="py-2.5 bg-blue-900 text-white rounded-lg font-bold text-xs">+2 Doble</button>
                        <button @click="activeTeamSide = 'away'; submitScore('point_basket', 3)" class="py-2.5 bg-blue-950 text-white rounded-lg font-bold text-xs">+3 Triple</button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="openGoalModal('away')" class="py-3 bg-blue-900 hover:bg-blue-950 text-white rounded-xl font-bold text-xs shadow-xs transition flex items-center justify-center space-x-2">
                            <span>+ Registrar Gol</span>
                        </button>
                        <button @click="openCardModal('away')" class="py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-xs transition flex items-center justify-center space-x-2">
                            <span>+ Tarjeta / Amonestación</span>
                        </button>
                    </div>
                @endif
            </div>

        </div>
    @endif

    <!-- MINUTE-BY-MINUTE TIMELINE & INCIDENTS HISTORY -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Línea de Tiempo / Minuto a Minuto</h3>
            <span class="text-xs text-slate-500 font-medium">Registro oficial de incidencias</span>
        </div>

        <div class="divide-y divide-slate-100 text-xs">
            @forelse($match->incidents as $inc)
                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center space-x-4">
                        <span class="font-mono font-bold text-slate-400 text-xs w-10">{{ $inc->minute }}'</span>
                        
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $inc->team->color ?? '#94a3b8' }}"></span>
                            <span class="font-extrabold text-slate-900">{{ $inc->team->name ?? 'Equipo' }}</span>
                        </div>

                        <div>
                            <span class="font-bold text-slate-800">
                                @if($inc->type === 'goal')
                                    GOL ({{ $inc->detail }})
                                @elseif($inc->type === 'penalty_kick')
                                    Penal ({{ $inc->detail }})
                                @elseif($inc->type === 'card_yellow')
                                    Tarjeta Amarilla
                                @elseif($inc->type === 'card_red')
                                    Tarjeta Roja
                                @elseif($inc->type === 'ace')
                                    Ace (Saque Ganador)
                                @else
                                    {{ strtoupper($inc->type) }}
                                @endif
                            </span>
                            @if($inc->player)
                                <span class="text-slate-500"> - {{ $inc->player->name }} (#{{ $inc->player->dorsal }})</span>
                            @endif
                        </div>
                    </div>

                    @if($isOrganizer)
                        <button @click="deleteIncident({{ $inc->id }})" class="text-rose-500 hover:text-rose-700 text-xs font-semibold px-2 py-1 rounded">
                            Deshacer / Eliminar
                        </button>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">Aún no hay incidencias registradas en este partido.</div>
            @endforelse
        </div>
    </div>

    <!-- GOAL REGISTRATION MODAL -->
    @if($isOrganizer)
        <div x-show="goalModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="goalModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="goalModal = false"></div>
                
                <div class="inline-block bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">Registrar Gol</h3>

                    <div class="space-y-4 mt-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Autor del Gol (Jugador)</label>
                            <select x-model="selectedPlayerId" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                                <option value="">-- Seleccionar Jugador --</option>
                                <template x-for="p in (activeTeamSide === 'home' ? {{ json_encode($match->homeTeam->players) }} : {{ json_encode($match->awayTeam->players) }})" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' (#' + (p.dorsal || '-') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipo de Anotación</label>
                            <select x-model="detail" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                                <option value="Jugada">Jugada Normal</option>
                                <option value="Tiro libre">Tiro Libre Directo</option>
                                <option value="Penal">Penal</option>
                                <option value="Cabeza">Remate de Cabeza</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="goalModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancelar</button>
                        <button @click="submitScore('goal')" class="px-5 py-2 bg-blue-900 text-white rounded-lg font-bold text-xs">Guardar Gol (+1)</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
