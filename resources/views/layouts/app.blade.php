<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $currentTournament->name ?? 'CanchaX' }} - Plataforma de Torneos Deportivos</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: #0f172a; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        @keyframes progressIndeterminate {
            0% { transform: translateX(-100%); width: 25%; }
            50% { transform: translateX(50%); width: 65%; }
            100% { transform: translateX(200%); width: 25%; }
        }
        .animate-progress-fast {
            animation: progressIndeterminate 1.3s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.35; transform: scale(1); }
            50% { opacity: 0.75; transform: scale(1.08); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 2.2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-white text-slate-900 min-h-screen flex flex-col antialiased" x-data="{
    activeModal: false,
    userMenu: false,
    notifOpen: false,
    unreadCount: 0,
    notifications: [],
    toastVisible: false,
    toastMsg: '',
    toastTitle: '',
    globalLoading: false,
    loadingTitle: 'Cargando...',
    loadingSubtitle: 'Por favor espera un momento...',
    loadingTimeout: null,

    init() {
        @auth
            this.fetchNotifications();
            this.connectSSE();
        @endauth

        window.addEventListener('show-global-loading', (e) => {
            this.startLoading(e.detail?.title, e.detail?.subtitle);
        });
        window.addEventListener('hide-global-loading', () => {
            this.stopLoading();
        });
    },
    startLoading(title = 'Cargando...', subtitle = 'Por favor espera un momento...') {
        this.loadingTitle = title || 'Cargando...';
        this.loadingSubtitle = subtitle || 'Por favor espera un momento...';
        this.globalLoading = true;
        clearTimeout(this.loadingTimeout);
        this.loadingTimeout = setTimeout(() => {
            this.globalLoading = false;
        }, 15000);
    },
    stopLoading() {
        clearTimeout(this.loadingTimeout);
        this.globalLoading = false;
    },
    fetchNotifications() {
        fetch('{{ route('notifications.index') }}')
            .then(res => res.json())
            .then(data => {
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.notifications || [];
            });
    },
    markAllRead() {
        fetch('{{ route('notifications.readAll') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            this.unreadCount = 0;
            this.notifications.forEach(n => n.read_at = new Date().toISOString());
        });
    },
    showToast(title, msg) {
        this.toastTitle = title;
        this.toastMsg = msg;
        this.toastVisible = true;
        setTimeout(() => { this.toastVisible = false; }, 6000);
    },
    connectSSE() {
        if (!window.EventSource) return;
        const source = new EventSource('/api/realtime/stream');
        
        source.addEventListener('notification_pushed', (e) => {
            const payload = JSON.parse(e.data);
            this.unreadCount = payload.unread_count || (this.unreadCount + 1);
            this.notifications.unshift(payload);
            const title = payload.data?.type === 'tournament_created' ? '🏆 Nuevo Torneo' : '⚡ Notificación en Vivo';
            this.showToast(title, payload.data?.message || 'Nueva actualización');
        });
    }
}">

    <!-- Header Navbar -->
    <header class="border-b border-slate-200 bg-white sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Brand Logo & Active Tournament Selector -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('landing') }}" class="flex items-center space-x-2">
                        <span class="font-extrabold text-xl tracking-tight text-slate-900">Cancha<span class="text-blue-900">X</span></span>
                    </a>

                    <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>

                    <!-- Active Tournament Switcher Button -->
                    @if(isset($currentTournament))
                        <button @click="activeModal = true" class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-800 transition">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="max-w-[140px] sm:max-w-[180px] truncate">{{ $currentTournament->name }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    @endif
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 font-medium text-sm">
                    <a href="{{ route('landing') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('landing') ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        Explorar
                    </a>
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        Resumen
                    </a>
                    <a href="{{ route('tournaments.bracket') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('tournaments.bracket') ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        Llaves
                    </a>
                    <a href="{{ route('matches.index') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('matches.*') ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        Partidos
                    </a>
                    <a href="{{ route('tournaments.standings') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('tournaments.standings') ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        Posiciones
                    </a>
                </nav>

                <!-- Auth Buttons & User Menu -->
                <div class="flex items-center space-x-3">
                    @auth
                        @if(auth()->user()->role === 'organizer')
                            <a href="{{ route('tournaments.wizard') }}" class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-blue-900 hover:bg-blue-950 transition shadow-sm">
                                + Nuevo Torneo
                            </a>
                        @endif

                        <!-- REALTIME NOTIFICATION BELL DROPDOWN -->
                        <div class="relative">
                            <button @click="notifOpen = !notifOpen" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 relative transition focus:outline-none" title="Notificaciones en Tiempo Real">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <template x-if="unreadCount > 0">
                                    <span class="absolute top-1 right-1 w-4 h-4 bg-rose-600 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse" x-text="unreadCount"></span>
                                </template>
                            </button>

                            <!-- Notification Dropdown -->
                            <div x-show="notifOpen" @click.away="notifOpen = false" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-50 text-xs">
                                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
                                    <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                        <span>🔔 Notificaciones en Tiempo Real</span>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                    </h4>
                                    <button @click="markAllRead()" class="text-[11px] text-blue-900 hover:underline font-semibold">Marcar leídas</button>
                                </div>

                                <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                                    <template x-for="n in notifications" :key="n.id">
                                        <div class="p-3 hover:bg-slate-50 transition" :class="!n.read_at ? 'bg-blue-50/40' : ''">
                                            <p class="font-bold text-slate-900 text-xs" x-text="n.data?.message || 'Nueva alerta'"></p>
                                            <span class="text-[10px] text-slate-400 block mt-1" x-text="n.created_at || 'Ahora'"></span>
                                        </div>
                                    </template>
                                    <template x-if="notifications.length === 0">
                                        <div class="p-6 text-center text-slate-400 text-xs">No tienes notificaciones pendientes.</div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button @click="userMenu = !userMenu" class="flex items-center space-x-2 focus:outline-none">
                                <span class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center shadow-xs" style="background-color: {{ auth()->user()->avatar_color ?? '#1e3a8a' }}">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden lg:block text-xs font-bold text-slate-800">{{ auth()->user()->name }}</span>
                            </button>

                            <div x-show="userMenu" @click.away="userMenu = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50 text-xs">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                    <span class="text-[10px] bg-blue-50 text-blue-900 font-bold px-1.5 py-0.5 rounded uppercase">
                                        {{ auth()->user()->role === 'organizer' ? 'Organizador' : 'Participante' }}
                                    </span>
                                </div>
                                <a href="{{ route('organizers.show', auth()->id()) }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">Mi Perfil y Torneos</a>
                                @if(auth()->user()->role === 'organizer')
                                    <a href="{{ route('tournaments.wizard') }}" class="block px-4 py-2 text-slate-700 hover:bg-slate-50">+ Crear Torneo</a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100 mt-1">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-rose-600 hover:bg-rose-50 font-semibold">Cerrar Sesión</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                            Iniciar Sesión
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 text-xs font-extrabold text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition shadow-xs">
                            Registrarse
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </header>

    <!-- Success Flash Message -->
    @if(session('success'))
        <div class="bg-slate-900 text-white px-4 py-2.5 text-center text-xs font-medium border-b border-slate-800 flex items-center justify-center space-x-2">
            <span class="inline-block w-2 h-2 bg-emerald-400 rounded-full"></span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Guest Mode Banner -->
    @guest
        <div class="bg-blue-50 border-b border-blue-100 text-blue-900 px-4 py-2 text-center text-xs font-medium flex items-center justify-center space-x-2">
            <span>👁️ Estás navegando en <strong>Modo Lectura Pública</strong>. Inicia sesión o regístrate para crear torneos y administrar partidos.</span>
            <a href="{{ route('login') }}" class="underline font-bold text-blue-950">Iniciar Sesión</a>
        </div>
    @endguest

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Active Tournaments Modal -->
    <div x-show="activeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div x-show="activeModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="activeModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="activeModal" x-transition.scale class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <div class="p-6 bg-white">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900">Catálogo de Torneos</h3>
                        <button @click="activeModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-3 max-h-80 overflow-y-auto pr-1">
                        @if(isset($allTournaments) && $allTournaments->count() > 0)
                            @foreach($allTournaments as $t)
                                <div class="p-4 rounded-lg border {{ $t->is_current ? 'border-blue-900 bg-blue-50/50' : 'border-slate-200 hover:border-slate-300' }} flex items-center justify-between transition">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-bold text-sm text-slate-900">{{ $t->name }}</h4>
                                            @if($t->is_current)
                                                <span class="bg-blue-900 text-white text-[10px] uppercase font-extrabold px-2 py-0.5 rounded">En Vista</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 uppercase font-semibold">
                                            {{ strtoupper($t->sport) }} &bull; {{ $t->location ?? 'Sede Central' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        @if(!$t->is_current)
                                            <a href="{{ route('tournaments.switch', $t->id) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-md text-xs font-semibold transition">
                                                Ver Torneo
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-xs text-slate-500 text-center py-4">No hay torneos registrados.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 mt-12 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between">
            <p>&copy; 2026 CanchaX. Plataforma Abierta de Gestión Deportivo.</p>
            <div class="mt-2 sm:mt-0 space-x-4">
                <a href="{{ route('landing') }}" class="hover:text-slate-900">Explorar</a>
                <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Resultados</a>
                <a href="{{ route('login') }}" class="hover:text-slate-900">Organizar</a>
            </div>
        </div>
    </footer>

    <!-- REALTIME FLOATING TOAST ALERT -->
    <div x-show="toastVisible" x-cloak x-transition.move.bottom.duration.300ms class="fixed bottom-6 right-6 z-50 max-w-sm bg-slate-900 text-white rounded-2xl p-4 shadow-2xl border-2 border-blue-600 flex items-start space-x-3">
        <div class="w-8 h-8 rounded-full bg-blue-900 flex items-center justify-center flex-shrink-0 text-white font-bold text-sm">
            ⚡
        </div>
        <div class="flex-1 text-xs">
            <h4 class="font-extrabold text-amber-400 text-sm" x-text="toastTitle"></h4>
            <p class="mt-1 text-slate-200" x-text="toastMsg"></p>
        </div>
        <button @click="toastVisible = false" class="text-slate-400 hover:text-white font-bold text-sm">
            &times;
        </button>
    </div>

    <!-- NANO TOP PROGRESS BAR FOR ALL URL / FORM LOADINGS -->
    <div x-show="globalLoading" x-cloak class="fixed top-0 left-0 right-0 h-1 bg-slate-900/30 z-[10001] overflow-hidden pointer-events-none">
        <div class="h-full bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-400 animate-progress-fast"></div>
    </div>

    <!-- PANTALLA DE CARGA GENERAL (FULLSCREEN OVERLAY) -->
    <div x-show="globalLoading" x-cloak 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md" 
         role="status" 
         aria-live="polite">
        
        <div x-show="globalLoading" 
             x-transition:enter="transition ease-out duration-250" 
             x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
             x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 scale-100" 
             x-transition:leave-end="opacity-0 scale-95" 
             class="relative bg-slate-900 border border-slate-700/80 rounded-3xl p-7 sm:p-9 max-w-sm w-full text-center shadow-2xl overflow-hidden">
            
            <!-- Ambient lighting glow blobs -->
            <div class="absolute -top-12 -left-12 w-36 h-36 bg-blue-600/30 rounded-full blur-2xl pointer-events-none animate-pulse-glow"></div>
            <div class="absolute -bottom-12 -right-12 w-36 h-36 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none animate-pulse-glow" style="animation-delay: 1s;"></div>

            <div class="relative z-10">
                <!-- Glowing Sports Spinner Ring -->
                <div class="relative w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                    <!-- Outer rotating gradient ring -->
                    <div class="absolute inset-0 rounded-full border-[3px] border-slate-800 border-t-blue-500 border-r-indigo-500 animate-spin"></div>
                    <!-- Inner reverse rotating ring -->
                    <div class="absolute inset-2 rounded-full border-[2px] border-slate-800 border-b-emerald-400 border-l-teal-400 animate-spin" style="animation-direction: reverse; animation-duration: 1.6s;"></div>
                    <!-- Center emblem -->
                    <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-900 to-slate-900 flex items-center justify-center shadow-lg border border-blue-400/30">
                        <span class="text-xl select-none animate-pulse">⚡</span>
                    </div>
                </div>

                <!-- Brand Badge -->
                <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-400 text-[10px] font-black uppercase tracking-widest mb-2.5">
                    <span>CanchaX En Vivo</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                </div>

                <!-- Dynamic Title -->
                <h3 class="text-lg font-black text-white tracking-tight leading-snug" x-text="loadingTitle"></h3>

                <!-- Dynamic Subtitle -->
                <p class="text-xs text-slate-300 mt-2 font-medium leading-relaxed" x-text="loadingSubtitle"></p>

                <!-- Animated Progress Bar -->
                <div class="w-full bg-slate-800/90 rounded-full h-1.5 mt-5 overflow-hidden border border-slate-700/60">
                    <div class="h-full bg-gradient-to-r from-blue-500 via-indigo-400 to-emerald-400 rounded-full animate-progress-fast"></div>
                </div>

                <!-- Micro Status Indicator -->
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono mt-3 px-1 font-semibold uppercase tracking-wider">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Sincronizando
                    </span>
                    <span>Por favor espera...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- GLOBAL URL NAVIGATION & ACTION LOADINGS INTERCEPTOR -->
    <script>
    (function() {
        window.showGlobalLoading = function(title, subtitle) {
            window.dispatchEvent(new CustomEvent('show-global-loading', { 
                detail: { title, subtitle } 
            }));
        };

        window.hideGlobalLoading = function() {
            window.dispatchEvent(new CustomEvent('hide-global-loading'));
        };

        // 1. URL Navigation / Change Listener for Links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href) return;

            // Ignorar enlaces especiales / internos sin navegación
            if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) {
                return;
            }
            if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loading')) {
                return;
            }
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
                return;
            }

            // Comprobar si es un enlace interno del mismo dominio
            try {
                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.origin !== window.location.origin) return;
                // Si es la misma ruta con sólo hash
                if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash) {
                    return;
                }
            } catch (err) {
                return;
            }

            let title = link.getAttribute('data-loading-title') || 'Cargando página...';
            let subtitle = link.getAttribute('data-loading-subtitle') || 'Navegando en CanchaX...';

            const text = (link.textContent || '').trim();
            if (text.includes('Registrarse')) {
                title = 'Abriendo Registro...';
                subtitle = 'Cargando opciones de perfil y ubicación';
            } else if (text.includes('Iniciar Sesión')) {
                title = 'Abriendo Inicio de Sesión...';
                subtitle = 'Accede a tu panel y torneos';
            } else if (href.includes('/tournaments/wizard') || text.includes('Crear Torneo') || text.includes('Organizar Torneo')) {
                title = 'Iniciando Asistente...';
                subtitle = 'Cargando creador de torneos, disciplinas y equipos';
            } else if (href.includes('/tournaments/switch')) {
                title = 'Cambiando de Torneo...';
                subtitle = 'Cargando datos, clasificaciones y fixtures';
            } else if (href.includes('/matches/live')) {
                title = 'Conectando a Mesa de Control...';
                subtitle = 'Sincronizando consola de arbitraje en vivo';
            } else if (href.includes('/tournaments/bracket')) {
                title = 'Cargando Llaves del Torneo...';
                subtitle = 'Renderizando cuadro de eliminatorias';
            } else if (href.includes('/tournaments/standings')) {
                title = 'Cargando Tablas de Posiciones...';
                subtitle = 'Calculando puntos, goles y clasificaciones';
            } else if (href.includes('/dashboard')) {
                title = 'Cargando Resultados...';
                subtitle = 'Obteniendo calendario y partidos recientes';
            } else if (href.includes('/teams')) {
                title = 'Cargando Equipos y Plantillas...';
                subtitle = 'Consultando jugadores y estadísticas';
            }

            window.showGlobalLoading(title, subtitle);
        });

        // 2. Forms & System Generation Interceptor
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || form.hasAttribute('data-no-loading')) return;

            let title = form.getAttribute('data-loading-title');
            let subtitle = form.getAttribute('data-loading-subtitle');
            const action = form.getAttribute('action') || '';

            if (!title) {
                if (action.includes('wizard')) {
                    title = '¡Generando Torneo y Fixtures!';
                    subtitle = 'El motor inteligente de CanchaX está creando llaves, calendario y grupos...';
                } else if (action.includes('register')) {
                    title = 'Creando tu Cuenta...';
                    subtitle = 'Guardando tus datos y configurando tu perfil en CanchaX...';
                } else if (action.includes('login')) {
                    title = 'Iniciando Sesión...';
                    subtitle = 'Verificando tus credenciales de acceso...';
                } else if (action.includes('teams') || action.includes('players')) {
                    title = 'Guardando Plantilla...';
                    subtitle = 'Actualizando información de equipos y atletas...';
                } else if (action.includes('collaborators')) {
                    title = 'Actualizando Colaboradores...';
                    subtitle = 'Configurando permisos de arbitraje y mesa...';
                } else if (action.includes('logout')) {
                    title = 'Cerrando Sesión...';
                    subtitle = 'Finalizando sesión de forma segura...';
                } else {
                    title = 'Procesando Operación...';
                    subtitle = 'Por favor espera unos instantes mientras el sistema procesa tu solicitud...';
                }
            }

            window.showGlobalLoading(title, subtitle);

            // Efecto visual en botones de envío
            const btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            btns.forEach(function(b) {
                b.classList.add('opacity-75', 'cursor-wait');
            });
        });

        // 3. Back / Forward cache recovery
        window.addEventListener('pageshow', function(e) {
            window.hideGlobalLoading();
        });
    })();
    </script>
</body>
</html>
