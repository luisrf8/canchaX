@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Organizer Profile Header -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 border border-slate-800 shadow-xl flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
        <div class="w-20 h-20 rounded-2xl text-white font-black text-3xl flex items-center justify-center flex-shrink-0 shadow-md" style="background-color: {{ $organizer->avatar_color ?? '#1e3a8a' }}">
            {{ strtoupper(substr($organizer->name, 0, 1)) }}
        </div>

        <div class="space-y-2 flex-1">
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ $organizer->name }}</h1>
                <span class="bg-blue-900 text-white text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded">
                    {{ strtoupper($organizer->role) }}
                </span>
            </div>

            <p class="text-xs text-slate-400 font-semibold">
                📍 {{ $organizer->city ?? 'España' }} &bull; Miembro de TorneoPro desde {{ $organizer->created_at->format('M Y') }}
            </p>

            <p class="text-xs text-slate-300 max-w-2xl font-normal">
                {{ $organizer->bio ?? 'Organizador apasionado por la gestión transparente y profesional de eventos deportivos.' }}
            </p>
        </div>

        <div class="flex items-center space-x-4 border-t md:border-t-0 md:border-l border-slate-800 pt-4 md:pt-0 md:pl-6 text-center">
            <div>
                <span class="text-2xl font-black text-white block tabular-nums">{{ $activeTournaments->count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase">Activos</span>
            </div>
            <div>
                <span class="text-2xl font-black text-white block tabular-nums">{{ $completedTournaments->count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase">Concluidos</span>
            </div>
        </div>
    </div>

    <!-- Active Tournaments by this Organizer -->
    <div class="space-y-4">
        <h2 class="text-xl font-extrabold text-slate-900 border-b border-slate-200 pb-3">Torneos Activos del Organizador</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($activeTournaments as $t)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <span class="bg-blue-900 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded uppercase">
                            {{ strtoupper($t->sport) }} &bull; {{ strtoupper($t->format) }}
                        </span>
                        <h3 class="font-extrabold text-base text-slate-900">{{ $t->name }}</h3>
                        <p class="text-xs text-slate-500 font-medium">📍 {{ $t->location ?? 'Sede Central' }}</p>
                    </div>

                    <a href="{{ route('tournaments.switch', $t->id) }}" class="w-full text-center py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">
                        Entrar al Torneo &rarr;
                    </a>
                </div>
            @empty
                <div class="col-span-full p-6 text-center text-slate-400 text-xs bg-slate-50 rounded-xl border border-slate-200">
                    Este organizador no tiene torneos activos actualmente.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Historical Completed Tournaments -->
    @if($completedTournaments->count() > 0)
        <div class="space-y-4">
            <h2 class="text-xl font-extrabold text-slate-900 border-b border-slate-200 pb-3">Torneos Realizados e Históricos</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($completedTournaments as $t)
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 space-y-4">
                        <div class="space-y-2">
                            <span class="bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-0.5 rounded uppercase">
                                {{ strtoupper($t->sport) }} &bull; FINALIZADO
                            </span>
                            <h3 class="font-extrabold text-base text-slate-900">{{ $t->name }}</h3>
                            <p class="text-xs text-slate-500 font-medium">📍 {{ $t->location ?? 'Sede Central' }}</p>
                        </div>

                        <a href="{{ route('tournaments.switch', $t->id) }}" class="w-full text-center py-2 bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold text-xs rounded-xl transition block">
                            Ver Histórico de Resultados
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
