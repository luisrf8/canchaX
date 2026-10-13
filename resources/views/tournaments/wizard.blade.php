@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10" x-data="{
    step: 1,
    sport: 'futbol',
    format: 'knockout',
    name: 'Copa CanchaX 2026',
    location: 'Polideportivo Municipal Central',
    periodDuration: 45,
    extraPeriodDuration: 15,
    knockoutTiebreak: 'prorroga_penales',
    customizeStages: false,
    stageRules: {
        '16vos': { tiebreak: 'penales_directo', leg_type: 'single' },
        'octavos': { tiebreak: 'penales_directo', leg_type: 'single' },
        'cuartos': { tiebreak: 'penales_directo', leg_type: 'single' },
        'semis': { tiebreak: 'penales_directo', leg_type: 'single' },
        'final': { tiebreak: 'prorroga_penales', leg_type: 'single' }
    },
    tiebreakPriority: ['direct', 'dg', 'gf', 'gc', 'fair_play'],
    teams: [
        { name: 'Águilas FC', short_name: 'AGU', color: '#1e3a8a', city: 'Madrid', coach: 'Carlos T.' },
        { name: 'Fénix Real', short_name: 'FNX', color: '#b91c1c', city: 'Barcelona', coach: 'Xavi H.' },
        { name: 'Rayo Azul', short_name: 'RAY', color: '#0369a1', city: 'Valencia', coach: 'Unai E.' },
        { name: 'Tigres del Norte', short_name: 'TIG', color: '#d97706', city: 'Sevilla', coach: 'Manuel P.' }
    ],
    selectSport(s) {
        this.sport = s;
        if (s === 'fifa') {
            this.periodDuration = 6;
            this.extraPeriodDuration = 3;
            this.knockoutTiebreak = 'penales_directo';
        } else if (s === 'futbol') {
            this.periodDuration = 45;
            this.extraPeriodDuration = 15;
            this.knockoutTiebreak = 'prorroga_penales';
        } else if (s === 'baloncesto') {
            this.periodDuration = 10;
            this.extraPeriodDuration = 5;
        }
    },
    addTeam() {
        let num = this.teams.length + 1;
        let defaultColors = ['#1e3a8a', '#b91c1c', '#0369a1', '#d97706', '#475569', '#854d0e', '#9f1239', '#15803d'];
        let color = defaultColors[(num - 1) % defaultColors.length];
        
        this.teams.push({
            name: 'Equipo #' + num,
            short_name: 'EQ' + num,
            color: color,
            city: 'Ciudad',
            coach: 'Entrenador'
        });
    },
    removeTeam(index) {
        if (this.teams.length <= 2) {
            alert('Un torneo debe tener al menos 2 equipos.');
            return;
        }
        this.teams.splice(index, 1);
    }
}">

    <!-- Page Title -->
    <div class="mb-8 text-center space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Asistente de Creación de Torneo</h1>
        <p class="text-sm text-slate-500">Personaliza la disciplina, tiempos, formato por fases y reglas de desempate exactas.</p>
    </div>

    <!-- Stepper Header -->
    <div class="mb-8 flex items-center justify-center space-x-4">
        <div class="flex items-center space-x-2">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition"
                  :class="step >= 1 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400'">1</span>
            <span class="text-xs font-bold" :class="step >= 1 ? 'text-slate-900' : 'text-slate-400'">Deporte, Duración y Reglas por Fase</span>
        </div>
        <div class="w-12 h-px bg-slate-200"></div>
        <div class="flex items-center space-x-2">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition"
                  :class="step >= 2 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400'">2</span>
            <span class="text-xs font-bold" :class="step >= 2 ? 'text-slate-900' : 'text-slate-400'">Configuración de Equipos</span>
        </div>
        <div class="w-12 h-px bg-slate-200"></div>
        <div class="flex items-center space-x-2">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition"
                  :class="step >= 3 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400'">3</span>
            <span class="text-xs font-bold" :class="step >= 3 ? 'text-slate-900' : 'text-slate-400'">Confirmación</span>
        </div>
    </div>

    <!-- Form Container -->
    <form action="{{ route('tournaments.storeWizard') }}" method="POST" data-loading-title="¡Generando Torneo y Fixtures!" data-loading-subtitle="El motor inteligente de CanchaX está creando llaves eliminatorias, grupos y calendario de partidos..." class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        @csrf

        <!-- STEP 1: Sport, Duration & Stage Rules -->
        <div x-show="step === 1" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Nombre del Torneo</label>
                    <input type="text" name="name" x-model="name" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-sm font-semibold text-slate-900" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Ubicación / Sede Oficial</label>
                    <input type="text" name="location" x-model="location" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-sm font-semibold text-slate-900" placeholder="Ej. Club Deportivo Central Madrid">
                </div>
            </div>

            <!-- Sport Selector Grid -->
            <div class="space-y-3 mt-6">
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Selecciona el Deporte / Disciplina</label>
                <input type="hidden" name="sport" :value="sport">

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <button type="button" @click="selectSport('futbol')" :class="sport === 'futbol' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Fútbol / Futsal</span>
                        <span class="block text-[10px] opacity-75 font-normal">Goles, 2 tiempos de 45m</span>
                    </button>

                    <button type="button" @click="selectSport('fifa')" :class="sport === 'fifa' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">FIFA / EA FC ⚽🎮</span>
                        <span class="block text-[10px] opacity-75 font-normal">Partidos de 6m o 10m</span>
                    </button>

                    <button type="button" @click="selectSport('tenis')" :class="sport === 'tenis' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Tenis</span>
                        <span class="block text-[10px] opacity-75 font-normal">Escala 0-15-30-40, Sets</span>
                    </button>

                    <button type="button" @click="selectSport('padel')" :class="sport === 'padel' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Pádel</span>
                        <span class="block text-[10px] opacity-75 font-normal">Punto de Oro a 40-40, Sets</span>
                    </button>

                    <button type="button" @click="selectSport('baloncesto')" :class="sport === 'baloncesto' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Baloncesto</span>
                        <span class="block text-[10px] opacity-75 font-normal">Triples, Dobles, 4 Cuartos</span>
                    </button>
                </div>
            </div>

            <!-- Match & Extra Time Durations -->
            <div class="bg-slate-50 rounded-xl p-5 border border-slate-200 space-y-3">
                <h4 class="text-xs font-extrabold uppercase text-slate-700">⏱️ Duración de Tiempos Normales y Prórroga</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Duración de Tiempos Normales (Minutos)</label>
                        <input type="number" name="period_duration" x-model="periodDuration" min="1" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono font-bold text-slate-900 bg-white">
                        <span class="text-[10px] text-slate-400">Ej. 6 min para FIFA / EA FC, 45 min para Fútbol real</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Duración de Tiempos Extra / Prórroga (Minutos)</label>
                        <input type="number" name="extra_period_duration" x-model="extraPeriodDuration" min="1" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono font-bold text-slate-900 bg-white">
                        <span class="text-[10px] text-slate-400">Ej. 3 min para FIFA / EA FC, 15 min para Fútbol real</span>
                    </div>
                </div>
            </div>

            <!-- Format Grid -->
            <div class="space-y-3 mt-6">
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Formato del Torneo</label>
                <input type="hidden" name="format" :value="format">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button type="button" @click="format = 'knockout'" :class="format === 'knockout' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-4 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Eliminación Directa (Llaves)</span>
                        <span class="block text-[11px] opacity-75 font-normal">Avance directo del ganador en 16vos, 8vos, 4tos, Semis y Final.</span>
                    </button>

                    <button type="button" @click="format = 'league'" :class="format === 'league' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-4 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Formato Liga</span>
                        <span class="block text-[11px] opacity-75 font-normal">Todos contra todos, tabla general de puntos.</span>
                    </button>

                    <button type="button" @click="format = 'groups'" :class="format === 'groups' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-4 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block text-sm font-extrabold">Fase de Grupos + Playoff</span>
                        <span class="block text-[11px] opacity-75 font-normal">Grupos A y B con avance a fases finales de eliminación.</span>
                    </button>
                </div>
            </div>

            <!-- LEAGUE / GROUP STAGE TIEBREAKER SETUP (For League and Groups) -->
            <div x-show="format === 'league' || format === 'groups'" class="bg-slate-50 rounded-xl p-5 border border-slate-200 space-y-3">
                <h4 class="text-xs font-extrabold uppercase text-slate-900">📊 Criterios de Desempate en Puntos (Fase de Grupos / Liga)</h4>
                <p class="text-[11px] text-slate-500">Determina qué criterio tiene mayor peso cuando dos o más equipos quedan igualados en puntos.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">1er Criterio Principal</label>
                        <select name="tiebreak_priority[]" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-bold bg-white">
                            <option value="direct" selected>Encuentro Directo (Resultado entre sí)</option>
                            <option value="dg">Gol Average / Diferencia de Goles</option>
                            <option value="gf">Goles a Favor</option>
                            <option value="fair_play">Fair Play / Menos Tarjetas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">2do Criterio Secundario</label>
                        <select name="tiebreak_priority[]" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-bold bg-white">
                            <option value="dg" selected>Gol Average / Diferencia de Goles</option>
                            <option value="gf">Goles a Favor</option>
                            <option value="direct">Encuentro Directo</option>
                            <option value="gc">Goles en Contra</option>
                            <option value="fair_play">Fair Play / Menos Tarjetas</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- STAGE-BY-STAGE GRANULAR KNOCKOUT RULES (For Knockout & Groups + Playoff) -->
            <div x-show="format === 'knockout' || format === 'groups'" class="bg-blue-50/60 rounded-xl p-5 border border-blue-200 space-y-4">
                <div class="flex items-center justify-between border-b border-blue-200 pb-3">
                    <div>
                        <h4 class="text-xs font-extrabold uppercase text-blue-950">⚔️ Reglas Granulares por Fase (16vos, 8vos, 4tos, Semis y Final)</h4>
                        <p class="text-[11px] text-blue-800 font-medium">Define el estilo exacto por cada ronda: Partido Único vs Ida y Vuelta, Prórroga vs Penales Directos.</p>
                    </div>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-blue-200">
                        <input type="checkbox" x-model="customizeStages" class="rounded border-slate-300 text-blue-900 focus:ring-blue-900">
                        <span class="text-xs font-bold text-slate-800">Personalizar por Fase</span>
                    </label>
                </div>

                <!-- Global Default Knockout Rule (If customizeStages === false) -->
                <div x-show="!customizeStages" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="hidden" name="knockout_tiebreak" :value="knockoutTiebreak">

                    <button type="button" @click="knockoutTiebreak = 'prorroga_penales'" :class="knockoutTiebreak === 'prorroga_penales' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block font-extrabold">Prórroga (2 Tiempos Extra) + Penales</span>
                        <span class="block text-[10px] opacity-75 font-normal">Todas las llaves juegan 2 tiempos extra de prórroga y luego penaltis si empatan.</span>
                    </button>

                    <button type="button" @click="knockoutTiebreak = 'penales_directo'" :class="knockoutTiebreak === 'penales_directo' ? 'border-blue-900 bg-blue-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold text-xs transition space-y-1">
                        <span class="block font-extrabold">Penales Directos (Sin Prórroga)</span>
                        <span class="block text-[10px] opacity-75 font-normal">Al empatar el tiempo regular, se pasa directamente a penaltis en todas las llaves.</span>
                    </button>
                </div>

                <!-- Stage-by-Stage Custom Overrides (If customizeStages === true) -->
                <div x-show="customizeStages" class="space-y-3 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        
                        <!-- 16vos -->
                        <div class="bg-white p-3 rounded-xl border border-blue-200 space-y-2 text-xs">
                            <span class="font-extrabold text-slate-900 uppercase block border-b border-slate-100 pb-1 text-[11px]">16vos de Final</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Formato Llave</label>
                                <select name="stage_rules[16vos][leg_type]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="single" selected>Partido Único</option>
                                    <option value="double">Ida y Vuelta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Regla Desempate</label>
                                <select name="stage_rules[16vos][tiebreak]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="penales_directo" selected>Penales Directos</option>
                                    <option value="prorroga_penales">Prórroga + Penales</option>
                                </select>
                            </div>
                        </div>

                        <!-- Octavos -->
                        <div class="bg-white p-3 rounded-xl border border-blue-200 space-y-2 text-xs">
                            <span class="font-extrabold text-slate-900 uppercase block border-b border-slate-100 pb-1 text-[11px]">Octavos de Final</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Formato Llave</label>
                                <select name="stage_rules[octavos][leg_type]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="single" selected>Partido Único</option>
                                    <option value="double">Ida y Vuelta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Regla Desempate</label>
                                <select name="stage_rules[octavos][tiebreak]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="penales_directo" selected>Penales Directos</option>
                                    <option value="prorroga_penales">Prórroga + Penales</option>
                                </select>
                            </div>
                        </div>

                        <!-- Cuartos -->
                        <div class="bg-white p-3 rounded-xl border border-blue-200 space-y-2 text-xs">
                            <span class="font-extrabold text-slate-900 uppercase block border-b border-slate-100 pb-1 text-[11px]">Cuartos de Final</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Formato Llave</label>
                                <select name="stage_rules[cuartos][leg_type]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="single" selected>Partido Único</option>
                                    <option value="double">Ida y Vuelta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Regla Desempate</label>
                                <select name="stage_rules[cuartos][tiebreak]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="penales_directo" selected>Penales Directos</option>
                                    <option value="prorroga_penales">Prórroga + Penales</option>
                                </select>
                            </div>
                        </div>

                        <!-- Semifinales -->
                        <div class="bg-white p-3 rounded-xl border border-blue-200 space-y-2 text-xs">
                            <span class="font-extrabold text-slate-900 uppercase block border-b border-slate-100 pb-1 text-[11px]">Semifinales</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Formato Llave</label>
                                <select name="stage_rules[semis][leg_type]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="single" selected>Partido Único</option>
                                    <option value="double">Ida y Vuelta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Regla Desempate</label>
                                <select name="stage_rules[semis][tiebreak]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="penales_directo" selected>Penales Directos</option>
                                    <option value="prorroga_penales">Prórroga + Penales</option>
                                </select>
                            </div>
                        </div>

                        <!-- Gran Final -->
                        <div class="bg-white p-3 rounded-xl border border-slate-900 space-y-2 text-xs">
                            <span class="font-extrabold text-blue-900 uppercase block border-b border-slate-100 pb-1 text-[11px]">Gran Final</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Formato Llave</label>
                                <select name="stage_rules[final][leg_type]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="single" selected>Partido Único</option>
                                    <option value="double">Ida y Vuelta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500">Regla Desempate</label>
                                <select name="stage_rules[final][tiebreak]" class="w-full px-1.5 py-1 rounded border border-slate-200 font-bold bg-white text-[11px]">
                                    <option value="prorroga_penales" selected>Prórroga + Penales</option>
                                    <option value="penales_directo">Penales Directos</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Next Button -->
            <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end">
                <button type="button" @click="step = 2" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition">
                    Siguiente: Configurar Equipos &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 2: DYNAMIC TEAMS SETUP ("+ Agregar equipo") -->
        <div x-show="step === 2" class="space-y-6" x-cloak>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Configuración Dinámica de Equipos</h3>
                    <p class="text-xs text-slate-500">Agrega cualquier número de participantes utilizando el botón "+ Agregar equipo".</p>
                </div>

                <div class="mt-3 sm:mt-0 flex items-center space-x-3">
                    <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg" x-text="teams.length + ' Equipos'"></span>
                    <button type="button" @click="addTeam()" class="px-4 py-2 bg-blue-900 hover:bg-blue-950 text-white text-xs font-extrabold rounded-lg shadow-sm transition">
                        + Agregar equipo
                    </button>
                </div>
            </div>

            <!-- Teams Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[420px] overflow-y-auto pr-1">
                <template x-for="(t, index) in teams" :key="index">
                    <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-slate-50/50 relative group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-slate-400" x-text="'Equipo #' + (index + 1)"></span>
                            
                            <div class="flex items-center space-x-2">
                                <input type="color" x-model="t.color" class="w-6 h-6 rounded cursor-pointer border-0">
                                <button type="button" @click="removeTeam(index)" class="text-rose-500 hover:text-rose-700 text-xs font-extrabold px-2 py-0.5 rounded border border-rose-200 hover:bg-rose-50 transition" title="Eliminar equipo">
                                    &times; Eliminar
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold uppercase text-slate-500">Nombre del Club / Pareja</label>
                                <input type="text" :name="'teams[' + index + '][name]'" x-model="t.name" class="w-full px-3 py-1.5 rounded border border-slate-200 text-xs font-bold text-slate-900 bg-white" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-500">Siglas</label>
                                <input type="text" :name="'teams[' + index + '][short_name]'" x-model="t.short_name" maxlength="4" class="w-full px-3 py-1.5 rounded border border-slate-200 text-xs font-mono font-bold uppercase text-slate-900 bg-white" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-500">Ciudad / Sede</label>
                                <input type="text" :name="'teams[' + index + '][city]'" x-model="t.city" class="w-full px-3 py-1.5 rounded border border-slate-200 text-xs text-slate-700 bg-white">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-500">DT / Entrenador</label>
                                <input type="text" :name="'teams[' + index + '][coach]'" x-model="t.coach" class="w-full px-3 py-1.5 rounded border border-slate-200 text-xs text-slate-700 bg-white">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                <button type="button" @click="step = 1" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900">
                    &larr; Volver
                </button>
                <button type="button" @click="step = 3" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition">
                    Siguiente: Revisar &rarr;
                </button>
            </div>
        </div>

        <!-- STEP 3: Review & Submit -->
        <div x-show="step === 3" class="space-y-6" x-cloak>
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-200 space-y-4">
                <h3 class="text-base font-bold text-slate-900">Resumen Previo a la Publicación</h3>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="block font-semibold text-slate-500 uppercase">Torneo</span>
                        <span class="font-extrabold text-slate-900" x-text="name"></span>
                    </div>
                    <div>
                        <span class="block font-semibold text-slate-500 uppercase">Sede</span>
                        <span class="font-extrabold text-slate-900" x-text="location"></span>
                    </div>
                    <div>
                        <span class="block font-semibold text-slate-500 uppercase">Deporte</span>
                        <span class="font-extrabold text-slate-900 uppercase" x-text="sport"></span>
                    </div>
                    <div>
                        <span class="block font-semibold text-slate-500 uppercase">Duración</span>
                        <span class="font-extrabold text-slate-900" x-text="periodDuration + 'm por tiempo (Prórroga: ' + extraPeriodDuration + 'm)'"></span>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <h4 class="text-xs font-bold uppercase text-slate-500">Participantes Confirmados (<span x-text="teams.length"></span>)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs max-h-48 overflow-y-auto">
                    <template x-for="t in teams">
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-white flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full" :style="'background-color:' + t.color"></span>
                            <span class="font-bold text-slate-900 truncate" x-text="t.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                <button type="button" @click="step = 2" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900">
                    &larr; Volver
                </button>
                <button type="submit" class="px-8 py-3 bg-blue-900 hover:bg-blue-950 text-white text-xs font-extrabold rounded-xl shadow-md transition">
                    ¡Publicar Torneo y Generar Fixture!
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
