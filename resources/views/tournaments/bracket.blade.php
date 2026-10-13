@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{
    modalOpen: false,
    selectedFormat: '{{ $playoffOptions[0]['key'] ?? 'auto' }}'
}">

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-bold text-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-5 gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-blue-900 uppercase tracking-wider">Árbol de Eliminación Directa</span>
                @if($tournament->format === 'groups' || $tournament->format === 'league')
                    @if($groupStageClosed)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border border-emerald-300">
                            ✓ Fase de Grupos Cerrada • Playoffs Oficiales
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border border-amber-300">
                            Fase de Grupos en Disputa
                        </span>
                    @endif
                @endif
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Llaves y Playoffs del Torneo</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('matches.index') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                Ver Lista de Partidos
            </a>
            <a href="{{ route('tournaments.standings') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                Ver Tablas de Posiciones
            </a>

            @if(auth()->check() && $tournament->canManage(auth()->user()))
                @if(($tournament->format === 'groups' || $tournament->format === 'league') && !$groupStageClosed)
                    <button type="button" @click="modalOpen = true" class="px-4 py-1.5 rounded-lg bg-blue-900 text-white text-xs font-black hover:bg-blue-950 transition flex items-center space-x-1.5 shadow-sm">
                        <span>🔒</span>
                        <span>Cerrar Fase de Grupos y Generar Llaves</span>
                    </button>
                @elseif(($tournament->format === 'groups' || $tournament->format === 'league') && $groupStageClosed)
                    <form method="POST" action="{{ route('tournaments.reopenGroups', $tournament->id) }}" onsubmit="return confirm('¿Estás seguro de reabrir la fase de grupos? Esto eliminará las llaves de playoffs no disputadas para permitir ajustes.');" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 text-xs font-black hover:bg-amber-100 transition flex items-center space-x-1">
                            <span>🔄</span>
                            <span>Reabrir Fase de Grupos</span>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    @php
        $octavos = $matches->where('stage', 'Octavos de final');
        $cuartos = $matches->where('stage', 'Cuartos de final');
        $semis = $matches->whereIn('stage', ['Semifinal', 'Semifinal 1', 'Semifinal 2']);
        $final = $matches->whereIn('stage', ['Gran Final', 'Gran Final Playoff', 'Final'])->first();
        $hasPlayoffMatches = ($octavos->count() > 0 || $cuartos->count() > 0 || $semis->count() > 0 || $final);
    @endphp

    {{-- CASO 1: FASE DE GRUPOS EN DISPUTA Y AÚN NO CERRADA --}}
    @if(!$groupStageClosed && !$hasPlayoffMatches && ($tournament->format === 'groups' || $tournament->format === 'league'))

        <!-- Panel de Estado de Grupos y Progreso -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 sm:p-8 shadow-xl border border-slate-700 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-black uppercase text-amber-400 tracking-wider">Etapa Regular en Curso</span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Fase de Grupos en Disputa</h2>
                    <p class="text-xs text-slate-300 max-w-2xl">
                        Los equipos están jugando la fase regular. Una vez completados los partidos o cuando decidas como organizador, puedes <strong>cerrar la fase de grupos</strong> para determinar los cruces oficiales de eliminación directa (Playoffs).
                    </p>
                </div>

                @if(auth()->check() && $tournament->canManage(auth()->user()))
                    <div class="flex-shrink-0">
                        <button type="button" @click="modalOpen = true" class="w-full sm:w-auto px-5 py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs uppercase tracking-wider transition shadow-md flex items-center justify-center space-x-2">
                            <span class="text-base">🔒</span>
                            <span>Cerrar Grupos y Crear Llaves</span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Progreso de Partidos de Grupo -->
            @php
                $pct = $totalGroupMatches > 0 ? round(($finishedGroupMatches / $totalGroupMatches) * 100) : 0;
            @endphp
            <div class="bg-slate-800/80 rounded-xl p-4 border border-slate-700/60 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-300 font-semibold">Progreso de Partidos de la Fase Regular:</span>
                    <span class="font-mono font-bold text-amber-400">{{ $finishedGroupMatches }} de {{ $totalGroupMatches }} partidos jugados ({{ $pct }}%)</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-amber-400 h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Proyección Actual de Clasificados a Playoffs -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-900">Proyección de Clasificados a Playoffs (Tiempo Real)</h3>
                    <p class="text-xs text-slate-500">Según la tabla de posiciones actual, estos son los puestos que avanzarían a la siguiente fase:</p>
                </div>
                <span class="text-[11px] font-mono text-slate-400 bg-slate-100 px-2 py-1 rounded">Regla: Victoria = 3 pts &bull; Empate = 1 pt</span>
            </div>

            <!-- Grid de Grupos con sus Clasificados Proyectados -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($groupStandings as $grpName => $rows)
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between text-xs">
                            <span class="font-black">{{ $grpName }}</span>
                            <span class="text-[10px] text-amber-400 font-mono">1° y 2° Clasifican</span>
                        </div>
                        <div class="p-3 divide-y divide-slate-100 text-xs">
                            @foreach($rows as $idx => $r)
                                <div class="py-2 flex items-center justify-between {{ $idx < 2 ? 'font-bold' : 'text-slate-500' }}">
                                    <div class="flex items-center space-x-2 truncate">
                                        <span class="w-5 h-5 rounded-full inline-flex items-center justify-center text-[10px] {{ $idx === 0 ? 'bg-amber-400 text-slate-950 font-black' : ($idx === 1 ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-slate-100 text-slate-400') }}">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $r['team']->color }}"></span>
                                        <span class="truncate">{{ $r['team']->name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2 font-mono text-[11px]">
                                        <span class="text-slate-400">{{ $r['dg'] > 0 ? '+'.$r['dg'] : $r['dg'] }} DG</span>
                                        <span class="font-black text-slate-900">{{ $r['pts'] }} PTS</span>
                                        @if($idx < 2)
                                            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded font-black">Clasifica</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tabla de Proyección de Mejores 3ros si existen 3 o más grupos -->
            @if(count($groupStandings) >= 3 && count($bestThirds) > 0)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs mt-6">
                    <div class="px-5 py-4 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm">⭐</span>
                                <h4 class="text-sm font-black text-white">Tabla de Proyección: Mejores Terceros Lugares</h4>
                            </div>
                            <p class="text-[11px] text-slate-400">En formato con 3 grupos (estilo Copa América), los 2 mejores terceros completan los 8 cupos de Cuartos de Final.</p>
                        </div>
                        <span class="text-[11px] bg-slate-800 text-amber-400 px-2.5 py-1 rounded font-mono font-bold">
                            Top 2 Avanzan a Cuartos
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4 text-center w-12">Rank</th>
                                    <th class="py-2.5 px-4">Equipo / Club</th>
                                    <th class="py-2.5 px-3 text-center">Grupo Origen</th>
                                    <th class="py-2.5 px-3 text-center">PJ</th>
                                    <th class="py-2.5 px-3 text-center">PG</th>
                                    <th class="py-2.5 px-3 text-center">PE</th>
                                    <th class="py-2.5 px-3 text-center">PP</th>
                                    <th class="py-2.5 px-3 text-center">GF</th>
                                    <th class="py-2.5 px-3 text-center">GC</th>
                                    <th class="py-2.5 px-3 text-center">DG</th>
                                    <th class="py-2.5 px-4 text-center font-black text-slate-900">PTS</th>
                                    <th class="py-2.5 px-4 text-center">Estado de Clasificación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                @foreach($bestThirds as $tIdx => $tRow)
                                    <tr class="hover:bg-slate-50 transition {{ $tIdx < 2 ? 'bg-emerald-50/30' : '' }}">
                                        <td class="py-3 px-4 text-center font-bold font-mono">
                                            <span class="w-6 h-6 rounded-full inline-flex items-center justify-center {{ $tIdx < 2 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                                {{ $tIdx + 1 }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $tRow['team']->color }}"></span>
                                                <span class="font-bold text-slate-900">{{ $tRow['team']->name }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center font-mono font-bold text-slate-600">
                                            {{ $tRow['group_name'] ?? 'Grupo' }}
                                        </td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['pj'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['pg'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['pe'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['pp'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['gf'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums">{{ $tRow['gc'] }}</td>
                                        <td class="py-3 px-3 text-center tabular-nums font-bold {{ $tRow['dg'] > 0 ? 'text-emerald-600' : ($tRow['dg'] < 0 ? 'text-rose-600' : 'text-slate-500') }}">
                                            {{ $tRow['dg'] > 0 ? '+'.$tRow['dg'] : $tRow['dg'] }}
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono font-black text-sm text-slate-900 bg-slate-50/50">
                                            {{ $tRow['pts'] }}
                                        </td>
                                        <td class="py-3 px-4 text-center font-bold text-[11px]">
                                            @if($tIdx < 2)
                                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✓ Clasifica a Cuartos (Mejor 3°)
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-400">
                                                    Eliminado de zona
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

    {{-- CASO 2: FASE DE GRUPOS CERRADA O TORNEO KNOCKOUT ACTIVO --}}
    @else

        <!-- Banner Informativo del Formato Aplicado -->
        @if($tournament->settings['group_stage_closed'] ?? false)
            <div class="bg-slate-900 text-white rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border border-slate-800 shadow-md">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🏆</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-black text-sm text-white">Playoffs Oficiales en Disputa</span>
                            <span class="bg-emerald-500 text-slate-950 font-black text-[9px] uppercase px-2 py-0.5 rounded">Fase Cerrada</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Los ganadores de cada cruce avanzan automáticamente a la siguiente ronda (Semifinales y Gran Final).
                        </p>
                    </div>
                </div>

                @if(count($groupStandings) >= 3 && count($bestThirds) >= 2)
                    <div class="text-[11px] bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg text-slate-300">
                        <span class="font-bold text-amber-400">Clasificados por Mejores 3ros:</span>
                        <span>{{ $bestThirds[0]['team']->name ?? '' }} ({{ $bestThirds[0]['pts'] }} pts) y {{ $bestThirds[1]['team']->name ?? '' }} ({{ $bestThirds[1]['pts'] }} pts)</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Champion Banner if Final is Finished -->
        @if($final && ($final->status === 'finished' || $final->current_period === 'Finalizado') && $final->winner)
            <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 rounded-2xl p-8 text-center space-y-3 shadow-xl border-2 border-amber-300">
                <span class="bg-slate-950 text-amber-400 text-xs font-black uppercase px-4 py-1.5 rounded-full tracking-widest inline-block shadow-md">
                    CAMPEÓN OFICIAL DEL TORNEO 🏆
                </span>
                <h2 class="text-4xl font-black tracking-tight text-slate-950">{{ $final->winner->name }}</h2>
                <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Ganador indiscutido de {{ $tournament->name }}</p>
            </div>
        @endif

        <!-- Dynamic Knockout Bracket Tree -->
        @php
            $hasOctavos = $octavos->count() > 0;
            $hasCuartos = $cuartos->count() > 0;
            $hasSemis = $semis->count() > 0;
            $hasFinal = $final !== null;

            // Determinar columnas de la cuadrícula
            $colsCount = ($hasOctavos ? 1 : 0) + ($hasCuartos ? 1 : 0) + ($hasSemis ? 1 : 0) + ($hasFinal ? 1 : 0);
        @endphp

        <div class="overflow-x-auto pb-8 pt-2">
            <div class="min-w-[850px] grid {{ $colsCount === 4 ? 'grid-cols-4' : ($colsCount === 3 ? 'grid-cols-3' : ($colsCount === 2 ? 'grid-cols-2' : 'grid-cols-1 max-w-md mx-auto')) }} gap-8 items-center py-4">

                <!-- COLUMNA OCTAVOS -->
                @if($hasOctavos)
                    <div class="space-y-6">
                        <div class="text-center font-black text-xs text-slate-400 uppercase tracking-wider mb-2">Octavos de Final</div>
                        @foreach($octavos as $match)
                            @include('tournaments.partials.match_bracket_card', ['match' => $match, 'matches' => $matches])
                        @endforeach
                    </div>
                @endif

                <!-- COLUMNA CUARTOS DE FINAL -->
                @if($hasCuartos)
                    <div class="space-y-6">
                        <div class="text-center font-black text-xs text-slate-400 uppercase tracking-wider mb-2">Cuartos de Final</div>
                        @foreach($cuartos as $match)
                            @include('tournaments.partials.match_bracket_card', ['match' => $match, 'matches' => $matches])
                        @endforeach
                    </div>
                @endif

                <!-- COLUMNA SEMIFINALES -->
                @if($hasSemis)
                    <div class="space-y-10">
                        <div class="text-center font-black text-xs text-slate-400 uppercase tracking-wider mb-2">Semifinales</div>
                        @foreach($semis as $match)
                            @include('tournaments.partials.match_bracket_card', ['match' => $match, 'matches' => $matches])
                        @endforeach
                    </div>
                @endif

                <!-- COLUMNA GRAN FINAL -->
                @if($hasFinal)
                    <div class="space-y-6">
                        <div class="text-center font-black text-xs text-blue-900 uppercase tracking-widest mb-2 flex items-center justify-center space-x-1.5">
                            <span>🏆</span>
                            <span>Gran Final</span>
                        </div>
                        <div class="scale-105 transform">
                            @include('tournaments.partials.match_bracket_card', ['match' => $final, 'matches' => $matches, 'isFinal' => true])
                        </div>
                    </div>
                @endif

            </div>
        </div>

    @endif

    <!-- MODAL: CERRAR FASE DE GRUPOS Y SELECCIONAR FORMATO DE PLAYOFFS -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="modalOpen = false" class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-6 transform transition-all">
            
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-900 text-white flex items-center justify-center text-lg font-black shadow-xs">🔒</span>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Cerrar Fase de Grupos y Generar Llaves</h3>
                        <p class="text-xs text-slate-500">Selecciona el formato de eliminación directa para los clasificados:</p>
                    </div>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('tournaments.closeGroups', $tournament->id) }}" onsubmit="window.dispatchEvent(new CustomEvent('show-global-loading', { detail: { title: 'Generando Llaves de Playoffs...', subtitle: 'Evaluando clasificados y armando cuadro eliminatorio...' } }))" class="space-y-5">
                @csrf

                <div class="space-y-3">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider">
                        Opciones de Llaves de Eliminación Directa:
                    </label>

                    <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                        @foreach($playoffOptions as $opt)
                            <label class="relative flex items-start p-3.5 rounded-xl border cursor-pointer transition select-none"
                                   :class="selectedFormat === '{{ $opt['key'] }}' ? 'border-blue-900 bg-blue-50/40 ring-2 ring-blue-900/20' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <div class="flex items-center h-5">
                                    <input type="radio" name="playoff_format" value="{{ $opt['key'] }}"
                                           x-model="selectedFormat"
                                           class="w-4 h-4 text-blue-900 border-slate-300 focus:ring-blue-900">
                                </div>
                                <div class="ml-3 flex-1 text-xs">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-black text-slate-900">{{ $opt['label'] }}</span>
                                        @if($opt['is_recommended'])
                                            <span class="bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase px-2 py-0.5 rounded-full">Recomendado</span>
                                        @endif
                                    </div>
                                    <p class="text-slate-500 mt-1 text-[11px] leading-relaxed">{{ $opt['description'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900 space-y-1">
                    <span class="font-black block">ℹ️ Información Importante:</span>
                    <span>Al cerrar la fase regular, las posiciones actuales quedarán fijadas y los equipos clasificados avanzarán a los partidos de playoffs correspondientes. Si lo necesitas luego, podrás reabrir la fase.</span>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-900 hover:bg-blue-950 text-white text-xs font-black transition shadow-md flex items-center space-x-1.5">
                        <span>🔒</span>
                        <span>Confirmar y Generar Llaves Oficiales</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
