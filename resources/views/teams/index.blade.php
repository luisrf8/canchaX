@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ createTeamModal: false, createPlayerModal: false, selectedTeamId: '' }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Gestión de Clubes y Plantillas</span>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Equipos y Jugadores Roster</h1>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <button @click="createTeamModal = true" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition shadow-xs">
                + Agregar Nuevo Equipo
            </button>
        </div>
    </div>

    <!-- Teams Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($teams as $team)
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:border-slate-400 transition flex flex-col justify-between">
                <div>
                    <!-- Team Header -->
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $team->color }}"></span>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">{{ $team->name }}</h3>
                                <p class="text-[11px] text-slate-400 font-mono font-semibold">SIGLAS: {{ $team->short_name }} &bull; {{ $team->city ?? 'Sede N/A' }}</p>
                            </div>
                        </div>

                        <form action="{{ route('teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este equipo y sus jugadores?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-rose-600 transition p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>

                    <!-- Coach & Roster Info -->
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between text-xs border-b border-slate-100 pb-2">
                            <span class="font-bold text-slate-500 uppercase">Director Técnico</span>
                            <span class="font-semibold text-slate-900">{{ $team->coach ?? 'No asignado' }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-700 uppercase">Plantilla de Jugadores ({{ $team->players->count() }})</span>
                                <button @click="selectedTeamId = {{ $team->id }}; createPlayerModal = true" class="text-[11px] font-bold text-blue-900 hover:underline">
                                    + Agregar Jugador
                                </button>
                            </div>

                            <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto pr-1 text-xs">
                                @forelse($team->players as $player)
                                    <div class="py-2 flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-5 h-5 rounded bg-slate-100 font-mono text-[10px] font-bold flex items-center justify-center text-slate-700">
                                                {{ $player->dorsal ?? '-' }}
                                            </span>
                                            <span class="font-bold text-slate-900">{{ $player->name }}</span>
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-semibold">{{ $player->position }}</span>
                                            
                                            <form action="{{ route('players.destroy', $player->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este jugador?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-300 hover:text-rose-600 transition">
                                                    &times;
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-[11px] text-slate-400 py-3 text-center">Sin jugadores agregados.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 p-3 border-t border-slate-100 text-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Roster Activo en Torneo</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- CREATE TEAM MODAL -->
    <div x-show="createTeamModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="createTeamModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="createTeamModal = false"></div>
            
            <form action="{{ route('teams.store') }}" method="POST" class="inline-block bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

                <h3 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">Agregar Nuevo Equipo</h3>

                <div class="space-y-4 mt-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nombre del Equipo</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="Ej. Real Madrid">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Abreviatura (Siglas)</label>
                            <input type="text" name="short_name" required maxlength="4" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono font-bold uppercase" placeholder="RMA">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Color de Identificación</label>
                            <input type="color" name="color" value="#1e3a8a" class="w-full h-9 p-1 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Ciudad / Sede</label>
                            <input type="text" name="city" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="Madrid">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Director Técnico (DT)</label>
                            <input type="text" name="coach" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="Carlo Ancelotti">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="createTeamModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-slate-900 text-white rounded-lg font-bold text-xs">Guardar Equipo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE PLAYER MODAL -->
    <div x-show="createPlayerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="createPlayerModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="createPlayerModal = false"></div>
            
            <form action="{{ route('players.store') }}" method="POST" class="inline-block bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                @csrf
                <input type="hidden" name="team_id" :value="selectedTeamId">

                <h3 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">Agregar Jugador a la Plantilla</h3>

                <div class="space-y-4 mt-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nombre Completo del Jugador</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="Ej. Luka Modric">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dorsal / Número</label>
                            <input type="number" name="dorsal" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-mono font-bold" placeholder="10">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Posición</label>
                            <select name="position" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium">
                                <option value="Portero">Portero / Guardameta</option>
                                <option value="Defensa">Defensa</option>
                                <option value="Mediocampista" selected>Mediocampista</option>
                                <option value="Delantero">Delantero</option>
                                <option value="Base">Base (Baloncesto)</option>
                                <option value="Alero">Alero (Baloncesto)</option>
                                <option value="Jugador">Jugador General</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="createPlayerModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancelar</button>
                    <button type="submit" class="px-5 py-2 bg-blue-900 text-white rounded-lg font-bold text-xs">Guardar Jugador</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
