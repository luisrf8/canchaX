@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Árbol de Eliminación Directa</span>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Llaves del Torneo</h1>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <a href="{{ route('matches.index') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                Ver Lista de Partidos
            </a>
        </div>
    </div>

    @php
        $cuartos = $matches->where('stage', 'Cuartos de final');
        $semis = $matches->whereIn('stage', ['Semifinal', 'Semifinal 1', 'Semifinal 2']);
        $final = $matches->where('stage', 'Gran Final')->first();
    @endphp

    @if($tournament->format !== 'knockout' && $cuartos->count() === 0 && $semis->count() === 0)
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-8 text-center space-y-3">
            <h3 class="text-base font-bold text-slate-900">Este torneo está configurado en Formato Liga / Fase de Grupos</h3>
            <p class="text-xs text-slate-500">Puedes consultar la tabla general de posiciones y partidos programados en la pestaña de Posiciones.</p>
            <a href="{{ route('tournaments.standings') }}" class="inline-block px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold">
                Ver Tabla de Posiciones &rarr;
            </a>
        </div>
    @else
        <!-- Champion Banner if Final is Finished -->
        @if($final && $final->status === 'finished' && $final->winner)
            <div class="bg-slate-900 text-white rounded-2xl p-6 text-center space-y-3 border-2 border-blue-900 shadow-xl">
                <span class="bg-amber-500 text-slate-950 text-[10px] font-black uppercase px-3 py-1 rounded-full tracking-widest">
                    CAMPEÓN OFICIAL 🏆
                </span>
                <h2 class="text-3xl font-black tracking-tight">{{ $final->winner->name }}</h2>
                <p class="text-xs text-slate-400">Ganador absoluto de la {{ $tournament->name }}</p>
            </div>
        @endif

        <!-- Knockout Brackets Tree -->
        <div class="overflow-x-auto pb-6">
            <div class="min-w-[900px] grid grid-cols-3 gap-8 items-center py-6">

                <!-- COLUMN 1: Cuartos de Final -->
                <div class="space-y-6">
                    <div class="text-center font-extrabold text-xs text-slate-400 uppercase tracking-wider mb-2">Cuartos de Final</div>
                    
                    @foreach($cuartos as $match)
                        @include('tournaments.partials.match_bracket_card', ['match' => $match])
                    @endforeach

                    @if($cuartos->count() === 0)
                        <div class="p-4 border border-dashed border-slate-200 rounded-xl text-center text-xs text-slate-400">
                            Llave sin cuartos de final
                        </div>
                    @endif
                </div>

                <!-- COLUMN 2: Semifinales -->
                <div class="space-y-12">
                    <div class="text-center font-extrabold text-xs text-slate-400 uppercase tracking-wider mb-2">Semifinales</div>

                    @foreach($semis as $match)
                        @include('tournaments.partials.match_bracket_card', ['match' => $match])
                    @endforeach
                </div>

                <!-- COLUMN 3: Gran Final -->
                <div class="space-y-6">
                    <div class="text-center font-extrabold text-xs text-blue-900 uppercase tracking-widest mb-2">Gran Final</div>

                    @if($final)
                        <div class="scale-105 transform">
                            @include('tournaments.partials.match_bracket_card', ['match' => $final, 'isFinal' => true])
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif

</div>
@endsection
