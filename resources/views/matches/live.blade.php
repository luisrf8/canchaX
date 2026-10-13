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
    elapsedSeconds: {{ $match->getLiveElapsedSeconds() }},
    timerRunning: {{ $match->timer_running && $match->status !== 'finished' && $match->current_period !== 'Finalizado' ? 'true' : 'false' }},
    period: '{{ $match->current_period }}',
    matchStatus: '{{ $match->status }}',
    isFinished: {{ $match->status === 'finished' || $match->current_period === 'Finalizado' ? 'true' : 'false' }},
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
    secondHalfModal: false,
    extraTimeModal: false,
    activeTeamSide: 'home',
    selectedPlayerId: '',
    selectedAssistId: '',
    detail: 'Jugada',
    cardType: 'card_yellow',
    selectedWinner: 'auto',

    init() {
        if (this.timerRunning && !this.isFinished) {
            this.startTimerInterval();
        }
        this.connectLiveMatchSSE();

        @if($isOrganizer)
            // Sincronización continua de respaldo en BD cada 15 segundos mientras corre el partido
            setInterval(() => {
                if (this.timerRunning && !this.isFinished) {
                    this.syncTimerServer();
                }
            }, 15000);
        @endif
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
            this.matchStatus = data.status;

            if (data.status === 'finished' || data.current_period === 'Finalizado') {
                this.isFinished = true;
                this.timerRunning = false;
                if (window.matchTimerInterval) clearInterval(window.matchTimerInterval);
            }

            const serverSeconds = data.elapsed_seconds;
            const isRunning = data.timer_running && !this.isFinished;

            @if(!$isOrganizer)
                // Sincronización con base de datos para espectadores
                if (Math.abs(this.elapsedSeconds - serverSeconds) > 2) {
                    this.elapsedSeconds = serverSeconds;
                }
                this.timerRunning = isRunning;
                if (this.timerRunning && !window.matchTimerInterval) {
                    this.startTimerInterval();
                } else if (!this.timerRunning && window.matchTimerInterval) {
                    clearInterval(window.matchTimerInterval);
                }
            @else
                // Sincronización para organizadores si hay cambio de estado o desviación
                if (this.timerRunning !== isRunning) {
                    this.timerRunning = isRunning;
                    if (this.timerRunning && !window.matchTimerInterval) {
                        this.startTimerInterval();
                    } else if (!this.timerRunning && window.matchTimerInterval) {
                        clearInterval(window.matchTimerInterval);
                    }
                }
                if (Math.abs(this.elapsedSeconds - serverSeconds) > 5) {
                    this.elapsedSeconds = serverSeconds;
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
            if (this.isFinished) return;
            this.timerRunning = !this.timerRunning;
            if (this.timerRunning) {
                this.startTimerInterval();
            } else {
                if (window.matchTimerInterval) clearInterval(window.matchTimerInterval);
            }
            this.syncTimerServer();
        @endif
    },
    startTimerInterval() {
        if (window.matchTimerInterval) clearInterval(window.matchTimerInterval);
        window.matchTimerInterval = setInterval(() => {
            if (this.timerRunning && !this.isFinished) {
                this.elapsedSeconds++;
            }
        }, 1000);
    },
    addSeconds(sec) {
        @if($isOrganizer)
            if (this.isFinished) return;
            this.elapsedSeconds = Math.max(0, this.elapsedSeconds + sec);
            this.syncTimerServer();
        @endif
    },
    updatePeriod(newPeriod) {
        @if($isOrganizer)
            if (newPeriod === 'Finalizado') {
                this.finishModal = true;
                return;
            }
            this.changePhase(newPeriod);
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
    changePhase(newPeriod, note = '', startTimer = false) {
        window.showGlobalLoading('Actualizando Periodo...', 'Cambiando estado a ' + newPeriod + '...');
        fetch('{{ route('matches.phase', $match->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                period: newPeriod,
                note: note,
                start_timer: startTimer,
                elapsed_seconds: this.elapsedSeconds
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.period = data.current_period;
                this.timerRunning = data.timer_running;
                this.matchStatus = data.status;
                if (newPeriod === 'Finalizado' || data.status === 'finished') {
                    this.isFinished = true;
                }
                window.location.reload();
            } else {
                window.hideGlobalLoading();
                alert(data.error || 'Error al actualizar periodo');
            }
        })
        .catch(() => window.hideGlobalLoading());
    },
    openGoalModal(side) {
        if (this.isFinished) return;
        this.activeTeamSide = side;
        this.selectedPlayerId = '';
        this.selectedAssistId = '';
        this.goalModal = true;
    },
    openCardModal(side) {
        if (this.isFinished) return;
        this.activeTeamSide = side;
        this.selectedPlayerId = '';
        this.cardModal = true;
    },
    submitScore(action, points = 1, isGoal = true) {
        if (this.isFinished) return;
        window.showGlobalLoading('Actualizando Marcador...', 'Registrando anotación y notificando en tiempo real...');
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
                is_goal: isGoal,
                elapsed_seconds: this.elapsedSeconds
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
            } else {
                window.hideGlobalLoading();
            }
        })
        .catch(() => window.hideGlobalLoading());
    },
    submitCard() {
        if (this.isFinished) return;
        window.showGlobalLoading('Registrando Sanción...', 'Añadiendo tarjeta a la ficha del partido...');
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
                reason: this.detail,
                elapsed_seconds: this.elapsedSeconds
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.cardModal = false;
                window.location.reload();
            } else {
                window.hideGlobalLoading();
            }
        })
        .catch(() => window.hideGlobalLoading());
    },
    deleteIncident(incidentId) {
        if (!confirm('¿Eliminar esta incidencia?')) return;
        window.showGlobalLoading('Eliminando Incidencia...', 'Actualizando evento en vivo...');
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
            } else {
                window.hideGlobalLoading();
            }
        })
        .catch(() => window.hideGlobalLoading());
    },
    openFinishModal() {
        this.selectedWinner = 'auto';
        this.finishModal = true;
    },
    submitFinish(winner = null) {
        const winnerChoice = winner || this.selectedWinner;
        window.showGlobalLoading('Finalizando Partido...', 'Cerrando acta oficial del partido...');
        fetch('{{ route('matches.finish', $match->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                winner: winnerChoice,
                elapsed_seconds: this.elapsedSeconds
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.isFinished = true;
                this.period = 'Finalizado';
                this.matchStatus = 'finished';
                this.timerRunning = false;
                this.finishModal = false;
                this.secondHalfModal = false;
                this.extraTimeModal = false;
                window.location.reload();
            } else {
                window.hideGlobalLoading();
                alert(data.error || 'Error al finalizar partido');
            }
        })
        .catch(() => window.hideGlobalLoading());
    }
}">

    <!-- Top Status Bar -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('matches.index') }}" class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
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
            <template x-if="isFinished">
                <div class="flex items-center space-x-2">
                    <span class="bg-emerald-100 text-emerald-800 font-extrabold text-xs px-3 py-1.5 rounded-lg border border-emerald-300 flex items-center space-x-1.5 shadow-xs">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span>✓ FINALIZADO</span>
                    </span>

                    @if($isOrganizer)
                        <form method="POST" action="{{ route('matches.reopen', $match->id) }}" onsubmit="return confirm('¿Seguro que deseas reactivar este partido? Volverá al estado EN VIVO para arbitraje.')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg shadow-sm transition flex items-center space-x-1">
                                <span>🔓 Reabrir Partido</span>
                            </button>
                        </form>
                    @endif
                </div>
            </template>

            <template x-if="!isFinished">
                <div class="flex items-center space-x-2">
                    @if($isOrganizer)
                        <button @click="openFinishModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                            Finalizar Partido
                        </button>
                    @else
                        <span class="bg-blue-50 text-blue-900 font-bold text-xs px-3 py-1.5 rounded-lg border border-blue-200">
                            👁️ Modo Lectura Pública
                        </span>
                    @endif
                </div>
            </template>
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
                
                <!-- IF MATCH IS FINISHED: NO BLINKING LIVE DOT -->
                <template x-if="isFinished">
                    <div class="flex flex-col items-center space-y-1.5">
                        <div class="bg-emerald-950/90 border border-emerald-500/50 text-emerald-400 text-xs font-extrabold px-3 py-1 rounded-full flex items-center space-x-1.5 font-mono shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>✓ PARTIDO CONCLUIDO</span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono">Tiempo Oficial Concluido</span>
                    </div>
                </template>

                <!-- IF MATCH IS LIVE (NOT FINISHED) -->
                <template x-if="!isFinished">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                        @if($isOrganizer)
                            <select x-model="period" @change="updatePeriod($el.value)" class="bg-slate-800 text-white font-mono text-xs font-bold px-3 py-1 rounded border border-slate-700 focus:ring-2 focus:ring-blue-500">
                                <option value="1T">1er Tiempo (1T - {{ $periodDuration }}m)</option>
                                <option value="Descanso">Descanso</option>
                                <option value="2T">2do Tiempo (2T - {{ $periodDuration }}m)</option>
                                <option value="1TE">1er Tiempo Extra ({{ $extraDuration }}m)</option>
                                <option value="Descanso Extra">Descanso Extra</option>
                                <option value="2TE">2do Tiempo Extra ({{ $extraDuration }}m)</option>
                                <option value="Penales">Tanda de Penales ⚽</option>
                                <option value="Finalizado">Finalizar Partido</option>
                            </select>
                        @else
                            <div class="inline-flex items-center space-x-1.5 bg-slate-800/90 text-slate-300 font-mono text-xs font-bold px-3 py-1 rounded-lg border border-slate-700">
                                <span class="text-rose-500 font-extrabold text-[10px]">EN VIVO:</span>
                                <span x-text="period" class="text-amber-400 font-bold"></span>
                            </div>
                        @endif
                    </div>
                </template>

                <!-- Stopwatch Large Display -->
                <div class="text-4xl sm:text-5xl font-mono font-black text-amber-400 tracking-wider tabular-nums">
                    <span x-text="formatTime(elapsedSeconds)"></span>
                </div>

                <!-- Timer Action Buttons (Organizer Only and When NOT Finished) -->
                @if($isOrganizer)
                    <template x-if="!isFinished">
                        <div class="flex items-center space-x-2">
                            <button @click="toggleTimer()" :class="timerRunning ? 'bg-amber-500 hover:bg-amber-600 text-slate-950' : 'bg-emerald-600 hover:bg-emerald-700 text-white'" class="px-4 py-1.5 rounded-lg text-xs font-extrabold transition">
                                <span x-text="timerRunning ? 'Pausar' : 'Iniciar Tiempo'"></span>
                            </button>
                            <button @click="addSeconds(60)" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg border border-slate-700">+1m</button>
                            <button @click="addSeconds(300)" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg border border-slate-700">+5m</button>
                        </div>
                    </template>
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

    <!-- CONTROL DE FASES Y PROGRESIÓN DEL PARTIDO (Solo Organizadores) -->
    @if($isOrganizer)
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                        <span>⏱️ Control de Fases y Periodos Oficiales</span>
                    </h3>
                    <p class="text-xs text-slate-500">Avanza ordenadamente por los tiempos reglamentarios, descansos, tiempos extra o penaltis.</p>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-500">Periodo actual:</span>
                    <span class="px-3 py-1 rounded-lg text-xs font-mono font-black"
                          :class="isFinished ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-blue-100 text-blue-900 border border-blue-300'"
                          x-text="isFinished ? 'Finalizado' : period"></span>
                </div>
            </div>

            <!-- Stepper Visual de Fases -->
            <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 text-center text-xs font-mono font-bold">
                <!-- 1T -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === '1T' && !isFinished ? 'bg-blue-900 text-white border-blue-950 ring-2 ring-blue-400' : (['Descanso', '2T', '1TE', 'Descanso Extra', '2TE', 'Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    1T
                </div>
                <!-- Descanso -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === 'Descanso' && !isFinished ? 'bg-amber-500 text-slate-950 border-amber-600 ring-2 ring-amber-300' : (['2T', '1TE', 'Descanso Extra', '2TE', 'Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    Descanso
                </div>
                <!-- 2T -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === '2T' && !isFinished ? 'bg-blue-900 text-white border-blue-950 ring-2 ring-blue-400' : (['1TE', 'Descanso Extra', '2TE', 'Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    2T
                </div>
                <!-- 1TE -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === '1TE' && !isFinished ? 'bg-purple-900 text-white border-purple-950 ring-2 ring-purple-400' : (['Descanso Extra', '2TE', 'Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    1T Extra
                </div>
                <!-- Descanso Extra -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === 'Descanso Extra' && !isFinished ? 'bg-amber-500 text-slate-950 border-amber-600 ring-2 ring-amber-300' : (['2TE', 'Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    Desc. Extra
                </div>
                <!-- 2TE -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === '2TE' && !isFinished ? 'bg-purple-900 text-white border-purple-950 ring-2 ring-purple-400' : (['Penales', 'Finalizado'].includes(period) || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    2T Extra
                </div>
                <!-- Penales -->
                <div class="p-2 rounded-lg border transition"
                     :class="period === 'Penales' && !isFinished ? 'bg-amber-600 text-white border-amber-700 ring-2 ring-amber-400' : (period === 'Finalizado' || isFinished ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-white text-slate-400 border-slate-200')">
                    Penales ⚽
                </div>
                <!-- Finalizado -->
                <div class="p-2 rounded-lg border transition"
                     :class="isFinished || period === 'Finalizado' ? 'bg-emerald-600 text-white border-emerald-700 ring-2 ring-emerald-400' : 'bg-white text-slate-400 border-slate-200'">
                    ✓ Final
                </div>
            </div>

            <!-- Botones de Acción Contextual según la Fase -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <!-- Si el partido está FINALIZADO -->
                <template x-if="isFinished">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-3 text-emerald-800">
                            <span class="text-2xl">🏆</span>
                            <div>
                                <h4 class="font-extrabold text-sm">Partido Oficialmente Finalizado</h4>
                                <p class="text-xs text-slate-600">Marcador y acta oficial cerrados. Las tablas del torneo han sido actualizadas.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('matches.reopen', $match->id) }}" onsubmit="return confirm('¿Seguro que deseas reactivar este partido? Volverá al estado EN VIVO para arbitraje.')">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-2">
                                <span>🔓 Reanudar / Reabrir Partido</span>
                            </button>
                        </form>
                    </div>
                </template>

                <!-- Si el partido NO está finalizado -->
                <template x-if="!isFinished">
                    <div class="space-y-4">
                        <!-- Caso: 1T -->
                        <div x-show="period === '1T'" class="flex flex-wrap items-center gap-3">
                            <button @click="changePhase('Descanso', 'Fin del 1er Tiempo. Descanso reglamentario iniciado.', false)"
                                    class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>☕ Finalizar 1er Tiempo (Ir a Descanso)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏁 Finalizar Partido Directamente</span>
                            </button>
                        </div>

                        <!-- Caso: Descanso -->
                        <div x-show="period === 'Descanso'" class="flex flex-wrap items-center gap-3">
                            <button @click="changePhase('2T', 'Inicio del 2do Tiempo reglamentario.', true)"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>▶️ Finalizar Descanso & Iniciar 2do Tiempo (2T)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏁 Finalizar Partido</span>
                            </button>
                        </div>

                        <!-- Caso: 2T -->
                        <div x-show="period === '2T'" class="flex flex-wrap items-center gap-3">
                            <button @click="secondHalfModal = true"
                                    class="px-5 py-2.5 bg-blue-900 hover:bg-blue-950 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>🏁 Finalizar 2do Tiempo (Concluir, Prórroga o Penales)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏆 Finalizar Partido Definitivo</span>
                            </button>
                        </div>

                        <!-- Caso: 1TE -->
                        <div x-show="period === '1TE'" class="flex flex-wrap items-center gap-3">
                            <button @click="changePhase('Descanso Extra', 'Fin del 1er Tiempo Extra.', false)"
                                    class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>☕ Finalizar 1er Tiempo Extra (Descanso Breve)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏁 Finalizar Partido</span>
                            </button>
                        </div>

                        <!-- Caso: Descanso Extra -->
                        <div x-show="period === 'Descanso Extra'" class="flex flex-wrap items-center gap-3">
                            <button @click="changePhase('2TE', 'Inicio del 2do Tiempo Extra.', true)"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>▶️ Finalizar Descanso & Iniciar 2do Tiempo Extra (2TE)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏁 Finalizar Partido</span>
                            </button>
                        </div>

                        <!-- Caso: 2TE -->
                        <div x-show="period === '2TE'" class="flex flex-wrap items-center gap-3">
                            <button @click="extraTimeModal = true"
                                    class="px-5 py-2.5 bg-purple-900 hover:bg-purple-950 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>🏁 Finalizar 2do Tiempo Extra (Concluir o Penales)</span>
                            </button>
                            <button @click="openFinishModal()"
                                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                                <span>🏆 Finalizar Partido Definitivo</span>
                            </button>
                        </div>

                        <!-- Caso: Penales -->
                        <div x-show="period === 'Penales'" class="flex flex-wrap items-center gap-3">
                            <button @click="openFinishModal()"
                                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2">
                                <span>🏆 Finalizar Tanda de Penales & Concluir Partido</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif

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
            <template x-if="!isFinished">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Home Team Penalty Controls -->
                    <div class="bg-white p-4 rounded-xl border border-amber-200 space-y-3">
                        <span class="font-bold text-xs text-slate-900 block" x-text="'Penal ' + '{{ $match->homeTeam->name }}'"></span>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="activeTeamSide = 'home'; submitScore('penalty_kick', 1, true)" class="py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs transition">
                                + Penal Anotado ⚽
                            </button>
                            <button @click="activeTeamSide = 'home'; submitScore('penalty_kick', 0, false)" class="py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs transition">
                                Penal Fallado ❌
                            </button>
                        </div>
                    </div>

                    <!-- Away Team Penalty Controls -->
                    <div class="bg-white p-4 rounded-xl border border-amber-200 space-y-3">
                        <span class="font-bold text-xs text-slate-900 block" x-text="'Penal ' + '{{ $match->awayTeam->name }}'"></span>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="activeTeamSide = 'away'; submitScore('penalty_kick', 1, true)" class="py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs transition">
                                + Penal Anotado ⚽
                            </button>
                            <button @click="activeTeamSide = 'away'; submitScore('penalty_kick', 0, false)" class="py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs transition">
                                Penal Fallado ❌
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        @endif
    </div>

    <!-- SPORT CONTROL ACTION BUTTONS (Organizers Only) -->
    @if($isOrganizer)
        <template x-if="isFinished">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-center text-slate-500 text-xs">
                🔒 Las acciones de juego (Goles, Puntos y Tarjetas) están deshabilitadas porque el partido está <strong class="text-slate-800">Finalizado</strong>. Para hacer modificaciones o registrar incidencias, reabre el partido desde el botón superior.
            </div>
        </template>

        <template x-if="!isFinished">
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
        </template>
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
                                @elseif($inc->type === 'period_change')
                                    ⏱️ {{ $inc->detail }}
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

    <!-- CARD REGISTRATION MODAL -->
    @if($isOrganizer)
        <div x-show="cardModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="cardModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="cardModal = false"></div>
                
                <div class="inline-block bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center space-x-2">
                        <span>🟨 Amonestación / Tarjeta</span>
                    </h3>

                    <div class="space-y-4 mt-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jugador Sancionado</label>
                            <select x-model="selectedPlayerId" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                                <option value="">-- Seleccionar Jugador --</option>
                                <template x-for="p in (activeTeamSide === 'home' ? {{ json_encode($match->homeTeam->players) }} : {{ json_encode($match->awayTeam->players) }})" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' (#' + (p.dorsal || '-') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tipo de Tarjeta</label>
                            <select x-model="cardType" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                                <option value="card_yellow">🟨 Tarjeta Amarilla</option>
                                <option value="card_red">🟥 Tarjeta Roja Directa</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Motivo / Causa</label>
                            <input type="text" x-model="detail" placeholder="Ej: Falta táctica, reclamo indebido, etc." class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="cardModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancelar</button>
                        <button @click="submitCard()" class="px-5 py-2 bg-slate-900 text-white rounded-lg font-bold text-xs">Guardar Tarjeta</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- FINISH 2ND HALF MODAL -->
    @if($isOrganizer)
        <div x-show="secondHalfModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="secondHalfModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="secondHalfModal = false"></div>
                
                <div class="inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all max-w-lg w-full p-6 z-10 border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                            <span>🏁 Finalizar Segundo Tiempo (2T)</span>
                        </h3>
                        <button @click="secondHalfModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                    </div>

                    <!-- Current Score Summary -->
                    <div class="my-4 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Marcador Reglamentario</span>
                        <div class="text-2xl font-black font-mono text-slate-900">
                            {{ $match->homeTeam->name }} <span x-text="homeScore"></span> - <span x-text="awayScore"></span> {{ $match->awayTeam->name }}
                        </div>
                        <template x-if="homeScore === awayScore">
                            <span class="inline-block mt-2 px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full">
                                ⚖️ Marcador empatado en tiempo reglamentario
                            </span>
                        </template>
                    </div>

                    <p class="text-xs text-slate-600 mb-4">¿Cómo deseas proceder según las reglas de la competición?</p>

                    <div class="space-y-2.5">
                        <!-- Option: Finalizar Partido -->
                        <button @click="submitFinish('auto'); secondHalfModal = false;"
                                class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition flex items-center justify-between group">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900 group-hover:text-emerald-900 block">🏆 Finalizar Partido Oficialmente</span>
                                <span class="text-[11px] text-slate-500 block">Concluye el encuentro con el marcador actual (victoria o empate registrado).</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-600 font-bold text-sm">&rarr;</span>
                        </button>

                        <!-- Option: Ir a Tiempos Extras (1TE) -->
                        <button @click="changePhase('1TE', 'Inicio de Tiempos Extras (1TE)', true); secondHalfModal = false;"
                                class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-purple-500 hover:bg-purple-50/50 transition flex items-center justify-between group">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900 group-hover:text-purple-900 block">⏱️ Iniciar Tiempos Extras (1TE - {{ $extraDuration }}m)</span>
                                <span class="text-[11px] text-slate-500 block">Jugar 1er tiempo suplementario por prórroga reglamentaria.</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-purple-600 font-bold text-sm">&rarr;</span>
                        </button>

                        <!-- Option: Ir Directo a Penales -->
                        <button @click="changePhase('Penales', 'Inicio de Tanda de Penaltis Directa', false); secondHalfModal = false;"
                                class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition flex items-center justify-between group">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900 group-hover:text-amber-900 block">⚽ Ir Directo a Tanda de Penales</span>
                                <span class="text-[11px] text-slate-500 block">Sin tiempos extras, resolver el desempate desde el punto penal.</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-amber-600 font-bold text-sm">&rarr;</span>
                        </button>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button @click="secondHalfModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- FINISH 2ND EXTRA TIME MODAL -->
    @if($isOrganizer)
        <div x-show="extraTimeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="extraTimeModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="extraTimeModal = false"></div>
                
                <div class="inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all max-w-lg w-full p-6 z-10 border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                            <span>🏁 Finalizar Segundo Tiempo Extra (2TE)</span>
                        </h3>
                        <button @click="extraTimeModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="my-4 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Marcador tras Tiempo Extra</span>
                        <div class="text-2xl font-black font-mono text-slate-900">
                            {{ $match->homeTeam->name }} <span x-text="homeScore"></span> - <span x-text="awayScore"></span> {{ $match->awayTeam->name }}
                        </div>
                        <template x-if="homeScore === awayScore">
                            <span class="inline-block mt-2 px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-full">
                                ⚽ Empate tras 120' de prórroga &bull; Se requiere definición por Penales
                            </span>
                        </template>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Option: Ir a Penales -->
                        <button @click="changePhase('Penales', 'Inicio de Tanda de Penaltis Oficial', false); extraTimeModal = false;"
                                class="w-full text-left p-3.5 rounded-xl border border-amber-300 bg-amber-50/50 hover:bg-amber-100/60 transition flex items-center justify-between group">
                            <div>
                                <span class="font-extrabold text-xs text-amber-950 block">⚽ Iniciar Tanda de Penales Oficial</span>
                                <span class="text-[11px] text-amber-800 block">Abrir mesa de control de penales para desempatar el clasificado.</span>
                            </div>
                            <span class="text-amber-600 font-bold text-sm">&rarr;</span>
                        </button>

                        <!-- Option: Finalizar Partido -->
                        <button @click="submitFinish('auto'); extraTimeModal = false;"
                                class="w-full text-left p-3.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition flex items-center justify-between group">
                            <div>
                                <span class="font-extrabold text-xs text-slate-900 group-hover:text-emerald-900 block">🏆 Finalizar Partido Definitivamente</span>
                                <span class="text-[11px] text-slate-500 block">Concluir encuentro y registrar el resultado obtenido.</span>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-600 font-bold text-sm">&rarr;</span>
                        </button>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button @click="extraTimeModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- FINISH MATCH MODAL -->
    @if($isOrganizer)
        <div x-show="finishModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="finishModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="finishModal = false"></div>
                
                <div class="inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center space-x-2">
                            <span>🏆 Finalizar Partido Oficialmente</span>
                        </h3>
                        <button @click="finishModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="my-4 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Marcador Registrado</span>
                        <div class="text-2xl font-black font-mono text-slate-900">
                            <span x-text="homeScore"></span> - <span x-text="awayScore"></span>
                        </div>
                        <template x-if="period === 'Penales' || homePenalties > 0 || awayPenalties > 0">
                            <div class="text-xs font-bold text-amber-700 mt-1 font-mono">
                                Penales: <span x-text="homePenalties"></span> - <span x-text="awayPenalties"></span>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Declarar Resultado / Ganador</label>
                            <select x-model="selectedWinner" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-900 bg-white">
                                <option value="auto">⚡ Automático (Según goles o penales registrados)</option>
                                <option value="home">Victoria Local ({{ $match->homeTeam->name }})</option>
                                <option value="away">Victoria Visitante ({{ $match->awayTeam->name }})</option>
                                <option value="draw">Empate Oficial (Sin Ganador)</option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">El ganador avanzará automáticamente en el cuadro si es una fase eliminatoria.</p>
                        </div>

                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900">
                            ⚠️ Al finalizar, el estatus pasará a <strong>Finalizado</strong>, el partido saldrá del estado En Vivo y se cerrará el cronómetro oficial.
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="finishModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cancelar</button>
                        <button @click="submitFinish()" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition">
                            ✓ Confirmar y Finalizar Partido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
