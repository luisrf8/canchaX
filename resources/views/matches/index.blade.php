@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ filter: 'all' }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200 pb-5">
        <div>
            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Mesa de Control y Transmisión</span>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Calendario de Partidos</h1>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
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
                    <div class="px-4 py-1.5 bg-slate-900 text-white rounded-lg font-mono font-black text-xl tabular-nums tracking-wider mx-3">
                        @if($m->status !== 'scheduled')
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

                <div class="pt-2 flex justify-between items-center border-t border-slate-50">
                    <span class="text-xs text-slate-400 font-semibold">
                        @if($m->winner)
                            Ganador: <strong class="text-slate-900">{{ $m->winner->name }}</strong>
                        @else
                            Periodo: {{ $isMatchFinished ? 'Finalizado' : $m->current_period }}
                        @endif
                    </span>

                    <a href="{{ route('matches.live', $m->id) }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition shadow-xs">
                        {{ $isMatchFinished ? 'Ver Resumen / Acta' : ($m->status === 'live' ? 'Mesa en Vivo →' : 'Transmitir / Arbitrar') }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
