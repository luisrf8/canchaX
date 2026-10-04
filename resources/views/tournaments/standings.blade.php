@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Header -->
    <div class="border-b border-slate-200 pb-5">
        <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Estadísticas Oficiales</span>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tabla de Posiciones y Clasificación</h1>
    </div>

    <!-- Active Tie-break Rules Banner -->
    <div class="bg-slate-900 text-white rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs space-y-2 sm:space-y-0 border border-slate-800">
        <div class="flex items-center space-x-2">
            <span class="text-base">📊</span>
            <span class="font-bold">Criterios de Desempate en Puntos Aplicados por el Creador:</span>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 font-mono text-[11px]">
            @php
                $labels = [
                    'direct' => '1° Encuentro Directo',
                    'dg' => '2° Gol Average (GF - GC)',
                    'gf' => '3° Goles a Favor',
                    'gc' => '4° Goles en Contra',
                    'fair_play' => '5° Fair Play'
                ];
            @endphp
            @foreach($priorityOrder as $p)
                <span class="bg-slate-800 text-amber-400 px-2 py-0.5 rounded font-bold">
                    {{ $labels[$p] ?? strtoupper($p) }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- General Standings Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Tabla General de Clasificación</h3>
            <span class="text-xs text-slate-500 font-medium">Criterio: Puntos &gt; Jerarquía de Desempate</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">Pos</th>
                        <th class="py-3 px-4">Equipo / Club</th>
                        <th class="py-3 px-3 text-center">PJ</th>
                        <th class="py-3 px-3 text-center">PG</th>
                        <th class="py-3 px-3 text-center">PE</th>
                        <th class="py-3 px-3 text-center">PP</th>
                        <th class="py-3 px-3 text-center">GF</th>
                        <th class="py-3 px-3 text-center">GC</th>
                        <th class="py-3 px-3 text-center">DG</th>
                        <th class="py-3 px-3 text-center">Fair Play</th>
                        <th class="py-3 px-4 text-center font-black text-slate-900">PTS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($standings as $index => $row)
                        <tr class="hover:bg-slate-50/80 transition {{ $index < 2 ? 'bg-blue-50/20' : '' }}">
                            <td class="py-3.5 px-4 text-center font-bold font-mono">
                                <span class="w-6 h-6 rounded-full inline-flex items-center justify-center {{ $index === 0 ? 'bg-amber-400 text-slate-950 font-black' : ($index === 1 ? 'bg-slate-200 text-slate-800' : 'text-slate-500') }}">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $row['team']->color }}"></span>
                                    <span class="font-bold text-slate-900">{{ $row['team']->name }}</span>
                                    <span class="text-[10px] font-mono text-slate-400">({{ $row['team']->short_name }})</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center tabular-nums">{{ $row['pj'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums font-semibold text-slate-900">{{ $row['pg'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums text-slate-500">{{ $row['pe'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums text-slate-500">{{ $row['pp'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums text-slate-600">{{ $row['gf'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums text-slate-600">{{ $row['gc'] }}</td>
                            <td class="py-3.5 px-3 text-center tabular-nums font-semibold {{ $row['dg'] > 0 ? 'text-emerald-600' : ($row['dg'] < 0 ? 'text-rose-600' : 'text-slate-500') }}">
                                {{ $row['dg'] > 0 ? '+'.$row['dg'] : $row['dg'] }}
                            </td>
                            <td class="py-3.5 px-3 text-center tabular-nums text-slate-500 font-mono">
                                Pts: {{ $row['fair_play'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-black text-sm text-slate-900 bg-slate-50/50">
                                {{ $row['pts'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid: Top Scorers & Fair Play -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Top Scorers Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Tabla de Goleadores (Pichichi)</h3>
                <p class="text-xs text-slate-500">Máximos anotadores del torneo.</p>
            </div>

            <div class="divide-y divide-slate-100 text-xs">
                @forelse($scorersData as $idx => $sc)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center space-x-3">
                            <span class="w-5 h-5 rounded-full font-bold flex items-center justify-center text-[10px] {{ $idx === 0 ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }}">
                                {{ $idx + 1 }}
                            </span>
                            <div>
                                <p class="font-bold text-slate-900">{{ $sc['player']->name }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold">{{ $sc['team']->name }} &bull; Dorsal #{{ $sc['player']->dorsal ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-black text-sm text-blue-900 block">{{ $sc['goals'] }} Goles</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $sc['assists'] }} Asistencias</span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400">No hay goles registrados aún.</div>
                @endforelse
            </div>
        </div>

        <!-- Fair Play Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Tabla de Fair Play y Tarjetas</h3>
                <p class="text-xs text-slate-500">Menor puntaje indica mejor conducta deportiva.</p>
            </div>

            <div class="divide-y divide-slate-100 text-xs">
                @foreach($fairPlay as $fp)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center space-x-3">
                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $fp['team']->color }}"></span>
                            <span class="font-bold text-slate-900">{{ $fp['team']->name }}</span>
                        </div>

                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-1">
                                <span class="w-2.5 h-3 bg-amber-400 rounded-xs inline-block"></span>
                                <span class="font-bold text-slate-800">{{ $fp['yellow'] }}</span>
                            </div>

                            <div class="flex items-center space-x-1">
                                <span class="w-2.5 h-3 bg-rose-600 rounded-xs inline-block"></span>
                                <span class="font-bold text-slate-800">{{ $fp['red'] }}</span>
                            </div>

                            <span class="font-mono font-bold text-slate-400 text-[11px]">Pts: {{ $fp['score'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
