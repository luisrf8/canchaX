@extends('layouts.app')

@section('content')
<div class="space-y-12 pb-12">

    <!-- HERO HEADER -->
    <div class="bg-slate-900 text-white py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden border-b border-slate-800">
        <div class="max-w-7xl mx-auto text-center space-y-6 relative z-10">
            <div class="inline-flex items-center space-x-2 bg-blue-900/60 border border-blue-700/50 px-3.5 py-1 rounded-full text-xs font-bold text-blue-200">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span>Plataforma Abierta de Gestión Deportiva</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-black tracking-tight max-w-4xl mx-auto leading-tight">
                Explora Torneos, Sedes y Marcadores en Tiempo Real
            </h1>

            <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto font-normal">
                Consulta los eventos creados por organizadores de la comunidad, revisa los árboles de llaves, tablas de clasificación y transmisiones en vivo sin necesidad de registro.
            </p>

            <div class="flex flex-wrap justify-center gap-4 pt-2">
                <a href="#torneos" class="px-6 py-3 bg-blue-900 hover:bg-blue-800 text-white text-xs font-extrabold rounded-xl shadow-lg transition">
                    Explorar Torneos Públicos
                </a>
                @auth
                    @if(auth()->user()->role === 'organizer')
                        <a href="{{ route('tournaments.wizard') }}" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-700 transition">
                            + Crear Mi Torneo
                        </a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-700 transition">
                        Registrarme como Organizador
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- METRICS COUNTER -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-center space-y-1">
                <p class="text-xs font-extrabold uppercase text-slate-400">Torneos Creados</p>
                <p class="text-3xl font-black text-slate-900 tabular-nums">{{ $totalTournaments }}</p>
            </div>
            <div class="text-center space-y-1">
                <p class="text-xs font-extrabold uppercase text-slate-400">Organizadores</p>
                <p class="text-3xl font-black text-blue-900 tabular-nums">{{ $totalOrganizers }}</p>
            </div>
            <div class="text-center space-y-1">
                <p class="text-xs font-extrabold uppercase text-slate-400">Partidos Regulados</p>
                <p class="text-3xl font-black text-slate-900 tabular-nums">{{ $totalMatches }}</p>
            </div>
            <div class="text-center space-y-1">
                <p class="text-xs font-extrabold uppercase text-slate-400">Clubes / Parejas</p>
                <p class="text-3xl font-black text-slate-900 tabular-nums">{{ $totalTeams }}</p>
            </div>
        </div>
    </div>

    <!-- TOURNAMENTS EXPLORER DIRECTORY -->
    <div id="torneos" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Comunidad Deportiva</span>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Torneos Públicos Disponibles</h2>
            </div>

            <!-- Sport Filters -->
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2 text-xs">
                <a href="{{ route('landing') }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ !$sport ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Todos
                </a>
                <a href="{{ route('landing', ['sport' => 'futbol']) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $sport === 'futbol' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Fútbol
                </a>
                <a href="{{ route('landing', ['sport' => 'tenis']) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $sport === 'tenis' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Tenis
                </a>
                <a href="{{ route('landing', ['sport' => 'padel']) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $sport === 'padel' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Pádel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($tournaments as $t)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs hover:border-slate-400 transition flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="bg-blue-900 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded uppercase">
                                {{ strtoupper($t->sport) }} &bull; {{ strtoupper($t->format) }}
                            </span>
                            @if($t->status === 'active')
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase flex items-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping mr-1"></span> Activo
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Concluido</span>
                            @endif
                        </div>

                        <h3 class="font-extrabold text-lg text-slate-900 leading-snug">{{ $t->name }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2">{{ $t->description ?? 'Sin descripción adicional.' }}</p>

                        <div class="pt-2 border-t border-slate-100 text-xs space-y-1">
                            <p class="text-slate-600 font-semibold flex items-center">
                                <span class="w-4 text-slate-400 font-normal">📍</span>
                                <span class="truncate">{{ $t->location ?? 'Sede Central' }}</span>
                            </p>
                            <p class="text-slate-600 font-semibold flex items-center">
                                <span class="w-4 text-slate-400 font-normal">👤</span>
                                <span>Organizador: </span>
                                @if($t->organizer)
                                    <a href="{{ route('organizers.show', $t->organizer->id) }}" class="font-bold text-blue-900 hover:underline ml-1">
                                        {{ $t->organizer->name }}
                                    </a>
                                @else
                                    <span class="font-bold text-slate-700 ml-1">CanchaX Staff</span>
                                @endif
                            </p>
                            <p class="text-slate-500 text-[11px]">
                                {{ $t->teams->count() }} Equipos registrados &bull; {{ $t->matches->count() }} Partidos
                            </p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a href="{{ route('tournaments.switch', $t->id) }}" class="w-full text-center py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">
                            Explorar Torneo y Resultados &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-50 border border-slate-200 rounded-xl p-8 text-center text-slate-500 text-xs">
                    No hay torneos públicos que coincidan con los filtros seleccionados.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ORGANIZERS DIRECTORY SECTION -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-200 pb-4">
            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Directorio de Usuarios</span>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Organizadores Destacados</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($organizers as $org)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-xl text-white font-black text-lg flex items-center justify-center flex-shrink-0 shadow-sm" style="background-color: {{ $org->avatar_color }}">
                        {{ strtoupper(substr($org->name, 0, 1)) }}
                    </div>

                    <div class="space-y-1 flex-1">
                        <h3 class="font-bold text-slate-900 text-base">{{ $org->name }}</h3>
                        <p class="text-xs text-slate-500 font-semibold">{{ $org->city ?? 'España' }} &bull; Organizador</p>
                        <p class="text-[11px] text-slate-400 line-clamp-2 mt-1">{{ $org->bio ?? 'Organizador activo de eventos deportivos locales.' }}</p>
                        
                        <div class="pt-3">
                            <a href="{{ route('organizers.show', $org->id) }}" class="text-xs font-bold text-blue-900 hover:underline">
                                Ver Perfil y Torneos ({{ $org->tournaments_count }}) &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- VENUES & LOCATIONS LIST -->
    @if($venues->count() > 0)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="border-b border-slate-200 pb-4">
                <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Ubicaciones y Sedes</span>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Centros Deportivos y Estadios</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($venues as $v)
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center space-x-3">
                        <span class="text-xl">📍</span>
                        <div>
                            <h4 class="font-bold text-slate-900 text-xs">{{ $v }}</h4>
                            <p class="text-[10px] text-slate-400 font-semibold">Sede Oficial de Torneos</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
