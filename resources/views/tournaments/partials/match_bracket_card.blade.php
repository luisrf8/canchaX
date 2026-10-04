<div class="bg-white rounded-xl border {{ isset($isFinal) && $isFinal ? 'border-2 border-slate-900 shadow-md' : 'border-slate-200 shadow-xs' }} overflow-hidden transition hover:border-slate-400">
    <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px]">
        <span class="font-bold text-slate-500 uppercase">{{ $match->stage }}</span>
        
        @if($match->status === 'live')
            <span class="bg-rose-600 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded uppercase flex items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping mr-1"></span> EN VIVO
            </span>
        @elseif($match->status === 'finished')
            <span class="text-slate-400 font-semibold uppercase text-[10px]">FINALIZADO</span>
        @else
            <span class="text-slate-400 font-semibold text-[10px]">{{ $match->scheduled_time ?? 'PROGRAMADO' }}</span>
        @endif
    </div>

    <div class="p-3 space-y-2 text-xs">
        <!-- Home Team Row -->
        <div class="flex items-center justify-between p-1.5 rounded {{ $match->winner_id && $match->winner_id === $match->home_team_id ? 'bg-slate-100 font-extrabold' : '' }}">
            <div class="flex items-center space-x-2 truncate">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $match->homeTeam->color ?? '#94a3b8' }}"></span>
                <span class="truncate text-slate-900 font-semibold">{{ $match->homeTeam->name ?? 'Por definir' }}</span>
            </div>
            <span class="font-mono font-bold text-slate-900 tabular-nums px-2">
                {{ $match->status !== 'scheduled' ? $match->home_score : '-' }}
            </span>
        </div>

        <!-- Away Team Row -->
        <div class="flex items-center justify-between p-1.5 rounded {{ $match->winner_id && $match->winner_id === $match->away_team_id ? 'bg-slate-100 font-extrabold' : '' }}">
            <div class="flex items-center space-x-2 truncate">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $match->awayTeam->color ?? '#94a3b8' }}"></span>
                <span class="truncate text-slate-900 font-semibold">{{ $match->awayTeam->name ?? 'Por definir' }}</span>
            </div>
            <span class="font-mono font-bold text-slate-900 tabular-nums px-2">
                {{ $match->status !== 'scheduled' ? $match->away_score : '-' }}
            </span>
        </div>
    </div>

    <!-- Direct Click to Live Control Console -->
    <div class="bg-slate-50 px-3 py-2 border-t border-slate-100 flex justify-end">
        <a href="{{ route('matches.live', $match->id) }}" class="text-[11px] font-bold text-blue-900 hover:text-blue-950 transition">
            {{ $match->status === 'live' ? 'Mesa de Control &rarr;' : 'Transmitir / Editar &rarr;' }}
        </a>
    </div>
</div>
