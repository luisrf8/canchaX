@extends('layouts.app')

@section('content')
@php
    $isOwner = auth()->check() && ($currentTournament->user_id === auth()->id());
    $isManager = auth()->check() && $currentTournament->canManage(auth()->user());
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ collaboratorModal: false }">

    <!-- Header Banner -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between shadow-lg relative overflow-hidden border border-slate-800">
        <div class="space-y-2 z-10">
            <div class="flex items-center space-x-3">
                <span class="bg-blue-900 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    {{ strtoupper($currentTournament->sport) }} &bull; {{ strtoupper($currentTournament->format) }}
                </span>
                <span class="text-emerald-400 text-xs font-semibold flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping mr-1.5"></span>
                    Torneo En Curso
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $currentTournament->name }}</h1>
            <p class="text-slate-400 text-xs sm:text-sm max-w-xl">
                📍 {{ $currentTournament->location ?? 'Sede Central' }} &bull; 👤 Organizador: <strong class="text-white">{{ $currentTournament->organizer->name ?? 'Staff' }}</strong>
            </p>

            @if(!$isManager)
                <div class="pt-1">
                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold px-3 py-1 rounded-lg">
                        👁️ Modo Lectura Pública (Solo el organizador "{{ $currentTournament->organizer->name ?? 'Staff' }}" o sus colaboradores pueden editar este torneo)
                    </span>
                </div>
            @endif
        </div>

        <div class="mt-6 md:mt-0 flex flex-wrap gap-3 z-10" x-data="{
            subscribed: {{ auth()->check() && auth()->user()->isSubscribedTo($currentTournament->id) ? 'true' : 'false' }},
            toggleSub() {
                fetch('{{ route('subscriptions.toggle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ tournament_id: {{ $currentTournament->id }} })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.subscribed = data.subscribed;
                });
            }
        }">
            <button @click="toggleSub()" :class="subscribed ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700'" class="px-4 py-2 text-xs font-bold rounded-lg transition shadow-sm flex items-center space-x-1.5">
                <span x-text="subscribed ? '🔔 Siguiendo (Notificaciones Activas)' : '🔔 Activar Notificaciones'"></span>
            </button>
            <a href="{{ route('tournaments.bracket') }}" class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold rounded-lg transition shadow-sm">
                Ver Llaves / Fixture
            </a>
            <a href="{{ route('matches.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-lg border border-slate-700 transition">
                Mesa de Control
            </a>
            @if($isOwner)
                <button @click="collaboratorModal = true" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg transition shadow-sm">
                    + Colaboradores ({{ $currentTournament->collaborators->count() }})
                </button>
            @endif
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Equipos Registrados</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ $teams->count() }}</span>
                <span class="text-xs font-medium text-slate-400">Clubes</span>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Partidos Jugados</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ $finishedMatches->count() }} / {{ $matches->count() }}</span>
                <span class="text-xs font-medium text-emerald-600 font-semibold">
                    {{ $matches->count() > 0 ? round(($finishedMatches->count() / $matches->count()) * 100) : 0 }}%
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Anotaciones</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-blue-900 tabular-nums">{{ $totalGoals }}</span>
                <span class="text-xs font-medium text-slate-400">Goles/Puntos</span>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jugadores en Roster</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-900 tabular-nums">{{ $totalPlayers }}</span>
                <span class="text-xs font-medium text-slate-400">Atletas</span>
            </div>
        </div>
    </div>

    <!-- Live Matches Section -->
    @if($liveMatches->count() > 0)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-rose-600 animate-ping"></span>
                    <span>Partidos En Vivo Transmitiendo</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($liveMatches as $lm)
                    <div class="bg-white border-2 border-slate-900 rounded-xl p-5 shadow-xs flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="text-xs font-bold text-slate-500 uppercase">{{ $lm->stage }}</span>
                            <span class="bg-rose-100 text-rose-800 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase">
                                {{ $lm->current_period }} &bull; EN VIVO
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-center px-4 py-2">
                            <div class="flex-1 text-left">
                                <span class="text-xs font-bold text-slate-400 block">{{ $lm->homeTeam->short_name ?? 'LOCAL' }}</span>
                                <h3 class="font-extrabold text-slate-900 text-base sm:text-lg truncate">{{ $lm->homeTeam->name ?? 'Equipo A' }}</h3>
                            </div>

                            <div class="px-6 py-2 bg-slate-900 text-white rounded-lg font-mono text-2xl font-black tracking-wider tabular-nums mx-4">
                                {{ $lm->home_score }} - {{ $lm->away_score }}
                            </div>

                            <div class="flex-1 text-right">
                                <span class="text-xs font-bold text-slate-400 block">{{ $lm->awayTeam->short_name ?? 'VISITA' }}</span>
                                <h3 class="font-extrabold text-slate-900 text-base sm:text-lg truncate">{{ $lm->awayTeam->name ?? 'Equipo B' }}</h3>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <a href="{{ route('matches.live', $lm->id) }}" class="w-full text-center py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition">
                                {{ $isManager ? 'Entrar a Mesa de Control &rarr;' : 'Ver Marcador en Vivo (Modo Lectura) &rarr;' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Authorized Collaborators Module (Only for Tournament Owner) -->
    @if($isOwner)
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">👥 Colaboradores y Árbitros Autorizados</h3>
                    <p class="text-xs text-slate-500">Sub-usuarios con permiso exclusivo para gestionar la Mesa de Control de ESTE torneo.</p>
                </div>
                <button @click="collaboratorModal = true" class="px-3.5 py-1.5 bg-blue-900 hover:bg-blue-950 text-white text-xs font-bold rounded-lg transition">
                    + Registrar Colaborador / Árbitro
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @forelse($currentTournament->collaborators as $col)
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-full text-white font-bold flex items-center justify-center" style="background-color: {{ $col->avatar_color }}">
                                {{ strtoupper(substr($col->name, 0, 1)) }}
                            </span>
                            <div>
                                <p class="font-bold text-slate-900">{{ $col->name }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $col->email }}</p>
                            </div>
                        </div>

                        <form action="{{ route('collaborators.destroy', [$currentTournament->id, $col->id]) }}" method="POST" onsubmit="return confirm('¿Remover acceso a este colaborador?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold text-xs px-2 py-1">
                                &times;
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="col-span-full py-4 text-center text-slate-400 text-xs">
                        No has añadido colaboradores todavía. Solo tú puedes editar este torneo.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Recent Matches -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Fixture del Torneo</h3>
                <p class="text-xs text-slate-500">Próximos partidos y resultados recientes.</p>
            </div>
            <a href="{{ route('matches.index') }}" class="text-xs font-bold text-blue-900 hover:underline">
                Ver Todos los Partidos &rarr;
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($matches->take(6) as $match)
                <div class="py-3.5 flex items-center justify-between text-sm">
                    <div class="w-28 text-xs font-semibold text-slate-400 uppercase truncate">
                        {{ $match->stage }}
                    </div>

                    <div class="flex-1 flex items-center justify-center space-x-4">
                        <span class="font-bold text-slate-900 text-right w-1/3 truncate">{{ $match->homeTeam->name ?? 'Por definir' }}</span>
                        
                        <div class="px-3 py-1 bg-slate-100 text-slate-900 font-mono font-bold rounded text-xs tabular-nums min-w-[70px] text-center">
                            @if($match->status === 'finished')
                                {{ $match->home_score }} - {{ $match->away_score }}
                            @elseif($match->status === 'live')
                                <span class="text-rose-600 font-extrabold">{{ $match->home_score }} - {{ $match->away_score }}</span>
                            @else
                                {{ $match->scheduled_time ?? 'VS' }}
                            @endif
                        </div>

                        <span class="font-bold text-slate-900 text-left w-1/3 truncate">{{ $match->awayTeam->name ?? 'Por definir' }}</span>
                    </div>

                    <div class="w-32 text-right">
                        <a href="{{ route('matches.live', $match->id) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                            {{ $match->status === 'live' ? 'En Vivo' : 'Ver Partido' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ADD COLLABORATOR MODAL -->
    @if($isOwner)
        <div x-show="collaboratorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div x-show="collaboratorModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="collaboratorModal = false"></div>
                
                <form action="{{ route('collaborators.store', $currentTournament->id) }}" method="POST" class="inline-block bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 z-10 border border-slate-200">
                    @csrf
                    <h3 class="text-lg font-bold text-slate-900 pb-3 border-b border-slate-100">Registrar Colaborador / Árbitro</h3>

                    <div class="space-y-4 mt-4 text-xs">
                        <p class="text-slate-500">Ingresa el correo del colaborador. Si no está registrado en TorneoPro, ingresa su nombre y contraseña para crear su usuario de arbitraje.</p>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Correo Electrónico</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="arbitro@torneopro.com">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombre Completo (Si es usuario nuevo)</label>
                            <input type="text" name="name" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="Ej. Juan Pérez (Árbitro)">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Contraseña (Si es usuario nuevo)</label>
                            <input type="password" name="password" class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="collaboratorModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-900 text-white rounded-lg font-bold text-xs">Autorizar Colaborador</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
