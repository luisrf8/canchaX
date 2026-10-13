@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{
    filter: 'all',
    quickScoreModal: false,
    quickMatchId: null,
    quickHomeName: '',
    quickAwayName: '',
    quickHomeColor: '#94a3b8',
    quickAwayColor: '#94a3b8',
    quickHomeScore: 0,
    quickAwayScore: 0,
    quickHomePenalties: 0,
    quickAwayPenalties: 0,
    quickIsFinished: true,
    quickWinner: 'auto',

    openQuickScore(id, home, away, hColor, aColor, hScore, aScore, hPen, aPen, isFin) {
        this.quickMatchId = id;
        this.quickHomeName = home;
        this.quickAwayName = away;
        this.quickHomeColor = hColor;
        this.quickAwayColor = aColor;
        this.quickHomeScore = hScore;
        this.quickAwayScore = aScore;
        this.quickHomePenalties = hPen || 0;
        this.quickAwayPenalties = aPen || 0;
        this.quickIsFinished = true;
        this.quickWinner = 'auto';
        this.quickScoreModal = true;
    },

    submitQuickScore() {
        if (!this.quickMatchId) return;
        window.showGlobalLoading('Guardando Marcador Directo...', 'Actualizando calendario y posiciones oficiales...');
        fetch('/matches/' + this.quickMatchId + '/direct-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                home_score: parseInt(this.quickHomeScore) || 0,
                away_score: parseInt(this.quickAwayScore) || 0,
                home_penalties: parseInt(this.quickHomePenalties) || 0,
                away_penalties: parseInt(this.quickAwayPenalties) || 0,
                is_finished: this.quickIsFinished,
                winner: this.quickWinner
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                window.hideGlobalLoading();
                alert(data.error || 'Error al guardar marcador directo');
            }
        })
        .catch(() => window.hideGlobalLoading());
    }
}">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-5 gap-4">
        <div>
            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Mesa de Control y Transmisión</span>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Calendario de Partidos</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">
                Todos ({{ $matches->count() }})
            </button>
            <button @click="filter = 'live'" :class="filter === 'live' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">
                En Vivo ({{ $matches->filter(fn($m) => $m->status === 'live' && $m->current_period !== 'Finalizado')->count() }})
            </button>
            <button @click="filter = 'scheduled'" :class="filter === 'scheduled' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">
                Programados
            </button>
            <button @click="filter = 'finished'" :class="filter === 'finished' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition">
                Finalizados ({{ $matches->filter(fn($m) => $m->status === 'finished' || $m->current_period === 'Finalizado')->count() }})
            </button>
        </div>
    </div>

    <!-- Matches Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($matches as $m)
            @php
                $isMatchFinished = $m->status === 'finished' || $m->current_period === 'Finalizado';
                $matchFilterKey = $isMatchFinished ? 'finished' : $m->status;
                $canManageMatch = auth()->check() && $m->tournament->canManage(auth()->user());
            @endphp
            <div x-show="filter === 'all' || filter === '{{ $matchFilterKey }}'" class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-slate-400 transition">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <span class="text-xs font-bold text-slate-500 uppercase">{{ $m->stage }}</span>
                    
                    @if($isMatchFinished)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded uppercase flex items-center">
                            ✓ FINALIZADO
                        </span>
                    @elseif($m->status === 'live')
                        <span class="bg-rose-600 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded uppercase flex items-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping mr-1"></span> EN VIVO
                        </span>
                    @else
                        <span class="text-slate-400 text-[11px] font-semibold">Programado</span>
                    @endif
                </div>

                <div class="flex items-center justify-between px-2">
                    <!-- Home -->
                    <div class="flex-1 text-left">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $m->homeTeam->color ?? '#94a3b8' }}"></span>
                            <span class="font-extrabold text-slate-900 text-base truncate">{{ $m->homeTeam->name ?? 'Por definir' }}</span>
                        </div>
                    </div>

                    <!-- Score -->
                    <div class="px-4 py-1.5 bg-slate-900 text-white rounded-lg font-mono font-black text-xl tabular-nums tracking-wider mx-3 text-center min-w-[70px]">
                        @if($m->status !== 'scheduled' || $isMatchFinished)
                            {{ $m->home_score }} - {{ $m->away_score }}
                        @else
                            {{ $m->scheduled_time ?? 'VS' }}
                        @endif
                    </div>

                    <!-- Away -->
                    <div class="flex-1 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <span class="font-extrabold text-slate-900 text-base truncate">{{ $m->awayTeam->name ?? 'Por definir' }}</span>
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $m->awayTeam->color ?? '#94a3b8' }}"></span>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex justify-between items-center border-t border-slate-50 gap-2">
                    <span class="text-xs text-slate-400 font-semibold truncate">
                        @if($m->winner)
                            Ganador: <strong class="text-slate-900">{{ $m->winner->name }}</strong>
                        @elseif($isMatchFinished)
                            Resultado: <strong class="text-slate-900">Empate</strong>
                        @else
                            Periodo: {{ $m->current_period }}
                        @endif
                    </span>

                    <div class="flex items-center space-x-2 flex-shrink-0">
                        @if($canManageMatch)
                            <button type="button" @click="openQuickScore({{ $m->id }}, '{{ addslashes($m->homeTeam->name ?? 'Local') }}', '{{ addslashes($m->awayTeam->name ?? 'Visita') }}', '{{ $m->homeTeam->color ?? '#94a3b8' }}', '{{ $m->awayTeam->color ?? '#94a3b8' }}', {{ (int)$m->home_score }}, {{ (int)$m->away_score }}, {{ (int)$m->home_penalties }}, {{ (int)$m->away_penalties }}, {{ $isMatchFinished ? 'true' : 'false' }})" class="px-3 py-2 bg-blue-900 hover:bg-blue-950 text-white text-xs font-black rounded-lg transition shadow-xs flex items-center space-x-1">
                                <span>⚡ Resultado</span>
                            </button>
                        @endif

                        <a href="{{ route('matches.live', $m->id) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-lg transition">
                            {{ $isMatchFinished ? 'Ver Acta' : ($m->status === 'live' ? 'En Vivo →' : 'Transmitir') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- MODAL: RESULTADO DIRECTO RÁPIDO DESDE CALENDARIO -->
    <div x-show="quickScoreModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="quickScoreModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-5 transform transition-all">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <span class="w-8 h-8 rounded-lg bg-blue-900 text-white flex items-center justify-center font-black text-sm">⚡</span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Cargar Resultado Directo</h3>
                        <p class="text-[11px] text-slate-500">Coloca el marcador final sin registrar gol por gol.</p>
                    </div>
                </div>
                <button type="button" @click="quickScoreModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <!-- Score Inputs Grid -->
            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <!-- Local -->
                <div class="space-y-1 text-center">
                    <div class="flex items-center justify-center space-x-1.5 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background-color: ' + quickHomeColor"></span>
                        <label class="font-bold text-xs text-slate-800 truncate" x-text="quickHomeName"></label>
                    </div>
                    <input type="number" min="0" x-model.number="quickHomeScore" class="w-full text-center py-3 bg-white rounded-xl border-2 border-slate-300 font-mono font-black text-3xl text-slate-900 focus:border-blue-900 focus:ring-0">
                </div>

                <!-- Visita -->
                <div class="space-y-1 text-center">
                    <div class="flex items-center justify-center space-x-1.5 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background-color: ' + quickAwayColor"></span>
                        <label class="font-bold text-xs text-slate-800 truncate" x-text="quickAwayName"></label>
                    </div>
                    <input type="number" min="0" x-model.number="quickAwayScore" class="w-full text-center py-3 bg-white rounded-xl border-2 border-slate-300 font-mono font-black text-3xl text-slate-900 focus:border-blue-900 focus:ring-0">
                </div>
            </div>

            <!-- Penalties Optional -->
            <div class="space-y-1">
                <label class="block font-bold text-xs text-slate-700">Penales (Opcional si hubo desempate):</label>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" min="0" placeholder="Penales Local" x-model.number="quickHomePenalties" class="w-full px-3 py-2 bg-white rounded-lg border border-slate-200 font-mono font-bold text-sm text-center">
                    <input type="number" min="0" placeholder="Penales Visita" x-model.number="quickAwayPenalties" class="w-full px-3 py-2 bg-white rounded-lg border border-slate-200 font-mono font-bold text-sm text-center">
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Criterio de Ganador:</label>
                    <select x-model="quickWinner" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 font-semibold text-slate-900 bg-white">
                        <option value="auto">⚡ Automático (Según goles o penales)</option>
                        <option value="home">Victoria Local</option>
                        <option value="away">Victoria Visitante</option>
                        <option value="draw">🤝 Empate Oficial (Fase regular / Liga)</option>
                    </select>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="quickIsFinished" class="w-4 h-4 text-blue-900 rounded border-slate-300 focus:ring-blue-900">
                        <span class="font-bold text-slate-900">Dar partido como Oficialmente Finalizado</span>
                    </label>
                    <p class="text-[11px] text-slate-500 pl-6">Actualizará inmediatamente la tabla general o avanzará las llaves de playoffs.</p>
                </div>
            </div>

            <div class="pt-2 flex justify-end space-x-3 border-t border-slate-100">
                <button type="button" @click="quickScoreModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cancelar</button>
                <button type="button" @click="submitQuickScore()" class="px-5 py-2.5 bg-blue-900 hover:bg-blue-950 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                    <span>💾</span>
                    <span>Guardar Marcador</span>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
