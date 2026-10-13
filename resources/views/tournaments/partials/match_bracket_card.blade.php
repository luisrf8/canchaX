@php
    $feederHome = isset($matches) ? $matches->first(fn($m) => $m->next_match_id === $match->id && $m->next_match_slot === 'home') : null;
    $homeIsPending = $feederHome && ($feederHome->status !== 'finished' && $feederHome->current_period !== 'Finalizado');
    $homeDisplayName = $homeIsPending ? 'Ganador ' . $feederHome->stage : ($match->homeTeam->name ?? 'Por definir');
    $homeColor = $homeIsPending ? '#cbd5e1' : ($match->homeTeam->color ?? '#94a3b8');

    $feederAway = isset($matches) ? $matches->first(fn($m) => $m->next_match_id === $match->id && $m->next_match_slot === 'away') : null;
    $awayIsPending = $feederAway && ($feederAway->status !== 'finished' && $feederAway->current_period !== 'Finalizado');
    $awayDisplayName = $awayIsPending ? 'Ganador ' . $feederAway->stage : ($match->awayTeam->name ?? 'Por definir');
    $awayColor = $awayIsPending ? '#cbd5e1' : ($match->awayTeam->color ?? '#94a3b8');
    $canPlay = !$homeIsPending && !$awayIsPending;
@endphp

<div class="bg-white rounded-xl border {{ isset($isFinal) && $isFinal ? 'border-2 border-slate-900 shadow-md ring-2 ring-amber-400/20' : 'border-slate-200 shadow-xs' }} overflow-hidden transition hover:border-slate-400">
    <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px]">
        <span class="font-bold text-slate-600 uppercase">{{ $match->stage }}</span>
        
        @if($match->status === 'live' && $match->current_period !== 'Finalizado')
            <span class="bg-rose-600 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded uppercase flex items-center shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping mr-1"></span> EN VIVO
            </span>
        @elseif($match->status === 'finished' || $match->current_period === 'Finalizado')
            <span class="text-emerald-700 font-bold uppercase text-[10px] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">✓ FINALIZADO</span>
        @elseif(!$canPlay)
            <span class="text-slate-400 font-semibold text-[10px] italic">POR DEFINIR</span>
        @else
            <span class="text-slate-500 font-semibold text-[10px] bg-slate-200/60 px-1.5 py-0.5 rounded">{{ $match->scheduled_time ?? 'PROGRAMADO' }}</span>
        @endif
    </div>

    <div class="p-3 space-y-2 text-xs">
        <!-- Home Team Row -->
        <div class="flex items-center justify-between p-1.5 rounded transition {{ $match->winner_id && $match->winner_id === $match->home_team_id ? 'bg-emerald-50 font-extrabold border border-emerald-200' : '' }}">
            <div class="flex items-center space-x-2 truncate">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $homeColor }}"></span>
                <span class="truncate font-semibold {{ $homeIsPending ? 'text-slate-400 italic' : 'text-slate-900' }}">
                    {{ $homeDisplayName }}
                </span>
            </div>
            <span class="font-mono font-bold text-slate-900 tabular-nums px-2">
                {{ $match->status !== 'scheduled' ? $match->home_score : '-' }}
            </span>
        </div>

        <!-- Away Team Row -->
        <div class="flex items-center justify-between p-1.5 rounded transition {{ $match->winner_id && $match->winner_id === $match->away_team_id ? 'bg-emerald-50 font-extrabold border border-emerald-200' : '' }}">
            <div class="flex items-center space-x-2 truncate">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $awayColor }}"></span>
                <span class="truncate font-semibold {{ $awayIsPending ? 'text-slate-400 italic' : 'text-slate-900' }}">
                    {{ $awayDisplayName }}
                </span>
            </div>
            <span class="font-mono font-bold text-slate-900 tabular-nums px-2">
                {{ $match->status !== 'scheduled' ? $match->away_score : '-' }}
            </span>
        </div>
    </div>

    <!-- Match Action Link -->
    <div class="bg-slate-50 px-3 py-2 border-t border-slate-100 flex items-center justify-between">
        @if(!$canPlay)
            <span class="text-[10px] text-slate-400 italic">Esperando resultados previos</span>
            <a href="{{ route('matches.live', $match->id) }}" class="text-[10px] text-slate-400 hover:text-slate-600">
                Ver detalle &rarr;
            </a>
        @else
            <span class="text-[10px] text-slate-400">Playoffs</span>
            <a href="{{ route('matches.live', $match->id) }}" class="text-[11px] font-bold text-blue-900 hover:text-blue-950 transition">
                {{ ($match->status === 'live' && $match->current_period !== 'Finalizado') ? 'Mesa de Control &rarr;' : (($match->status === 'finished' || $match->current_period === 'Finalizado') ? 'Ver Resumen &rarr;' : 'Transmitir / Arbitrar &rarr;') }}
            </a>
        @endif
    </div>
</div>
