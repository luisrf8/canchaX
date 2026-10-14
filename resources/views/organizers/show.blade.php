@extends('layouts.app')

@section('content')
@php
    $isOwner = auth()->check() && (auth()->id() === $organizer->id || auth()->user()->role === 'admin');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ editModal: {{ $errors->any() ? 'true' : 'false' }} }">

    <!-- Organizer Profile Header -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 border border-slate-800 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 z-10 flex-1">
            <div class="w-20 h-20 rounded-2xl text-white font-black text-3xl flex items-center justify-center flex-shrink-0 shadow-md ring-2 ring-white/10" style="background-color: {{ $organizer->avatar_color ?? '#1e3a8a' }}">
                {{ strtoupper(substr($organizer->name, 0, 1)) }}
            </div>

            <div class="space-y-1.5 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ $organizer->name }}</h1>
                    <span class="bg-blue-900 text-white text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded shadow-2xs">
                        {{ strtoupper($organizer->role) }}
                    </span>
                    @if($isOwner)
                        <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] uppercase font-bold px-2 py-0.5 rounded">
                            Tu Perfil
                        </span>
                    @endif
                </div>

                <p class="text-xs text-slate-400 font-semibold flex items-center gap-1.5">
                    <span>📍 {{ $organizer->location_string }}</span>
                    <span>&bull;</span>
                    <span>Miembro de CanchaX desde {{ $organizer->created_at->format('M Y') }}</span>
                </p>

                <p class="text-xs text-slate-300 max-w-2xl font-normal leading-relaxed pt-0.5">
                    {{ $organizer->bio ?? 'Organizador apasionado por la gestión transparente y profesional de eventos deportivos.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-6 z-10 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 md:border-l border-slate-800 pt-4 md:pt-0 md:pl-6">
            <div class="flex items-center space-x-4 text-center">
                <div>
                    <span class="text-2xl font-black text-white block tabular-nums">{{ $activeTournaments->count() }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Activos</span>
                </div>
                <div>
                    <span class="text-2xl font-black text-white block tabular-nums">{{ $completedTournaments->count() }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Concluidos</span>
                </div>
            </div>

            @if($isOwner)
                <button @click="editModal = true" class="px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center space-x-1.5 flex-shrink-0 cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Editar Mi Perfil</span>
                </button>
            @endif
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

    <!-- EDIT PROFILE MODAL (FOR ORGANIZER OWNER) -->
    @if($isOwner)
        <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div x-show="editModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="editModal" x-transition.scale class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200"
                     x-data="profileFormManager(@json($organizer))">
                    
                    <!-- Modal Header -->
                    <div class="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                        <div>
                            <h3 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                                <span>✏️ Editar Mi Perfil</span>
                            </h3>
                            <p class="text-xs text-slate-400">Actualiza tus datos públicos, ubicación y personalización de avatar.</p>
                        </div>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-white text-2xl font-bold transition">
                            &times;
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form method="POST" action="{{ route('profile.update') }}" class="divide-y divide-slate-100">
                        @csrf

                        <!-- Avatar and Basic Info -->
                        <div class="p-6 space-y-4">
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <!-- Live Avatar Preview -->
                                <div class="w-16 h-16 rounded-2xl text-white font-black text-2xl flex items-center justify-center shadow-md ring-2 ring-slate-200 flex-shrink-0 transition-colors"
                                     :style="{ backgroundColor: avatarColor }">
                                    <span x-text="name ? name.trim().charAt(0).toUpperCase() : 'U'"></span>
                                </div>

                                <div class="flex-1 space-y-1.5 text-center sm:text-left">
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Color del Avatar</label>
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5">
                                        <template x-for="c in colorPalette" :key="c.hex">
                                            <button type="button" 
                                                    @click="avatarColor = c.hex" 
                                                    :style="{ backgroundColor: c.hex }"
                                                    :title="c.name"
                                                    :class="avatarColor.toLowerCase() === c.hex.toLowerCase() ? 'ring-2 ring-slate-900 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                                                    class="w-6 h-6 rounded-lg transition shadow-2xs">
                                            </button>
                                        </template>
                                        <input type="color" x-model="avatarColor" class="w-6 h-6 rounded cursor-pointer border-0 p-0 ml-1" title="Color personalizado">
                                    </div>
                                    <input type="hidden" name="avatar_color" :value="avatarColor">
                                </div>
                            </div>

                            <!-- Name & Email Inputs -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" x-model="name" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico <span class="text-rose-500">*</span></label>
                                    <input type="email" name="email" x-model="email" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1">
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación Geográfica -->
                        <div class="p-6 bg-slate-50/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">📍 Ubicación</label>
                                <span class="text-[10px] font-bold text-blue-900 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/60" x-text="country === 'Venezuela' ? '🇻🇪 Venezuela' : '🌍 ' + country"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">País</label>
                                    <select x-model="country" @change="onCountryChange()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white">
                                        <template x-for="c in countries" :key="c">
                                            <option :value="c" x-text="c" :selected="c === country"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="country === 'Venezuela'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Estado</label>
                                    <select x-model="state" @change="onStateChange()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white">
                                        <option value="">Selecciona estado...</option>
                                        <template x-for="st in statesList" :key="st">
                                            <option :value="st" x-text="st" :selected="st === state"></option>
                                        </template>
                                    </select>
                                </div>

                                <div x-show="country === 'Venezuela'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Ciudad</label>
                                    <select x-model="city" :disabled="!state" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white disabled:bg-slate-100 disabled:text-slate-400">
                                        <option value="" x-text="!state ? 'Elige estado...' : 'Selecciona ciudad...'"></option>
                                        <template x-for="ct in citiesList" :key="ct">
                                            <option :value="ct" x-text="ct" :selected="ct === city"></option>
                                        </template>
                                        <option value="__other__" x-show="state">Otra ciudad / población...</option>
                                    </select>
                                </div>

                                <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Estado / Región</label>
                                    <input type="text" x-model="customState" placeholder="Ej. Antioquia" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white">
                                </div>

                                <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Ciudad</label>
                                    <input type="text" x-model="customCity" placeholder="Ej. Medellín" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white">
                                </div>
                            </div>

                            <div x-show="country === 'Venezuela' && city === '__other__'" x-cloak class="pt-1">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Especifica tu Población en <span x-text="state"></span></label>
                                <input type="text" x-model="customCity" placeholder="Ej. Punta de Mata, Caripe, etc." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-900 bg-white">
                            </div>

                            <input type="hidden" name="country" :value="country">
                            <input type="hidden" name="state" :value="finalState">
                            <input type="hidden" name="city" :value="finalCity">
                        </div>

                        <!-- Biografía -->
                        <div class="p-6 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Biografía y Presentación</label>
                            <textarea name="bio" rows="2" x-model="bio" placeholder="Describe brevemente tus torneos, categorías o experiencia deportiva..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-900 focus:border-slate-900 focus:ring-1"></textarea>
                        </div>

                        <!-- Seguridad (Opcional) -->
                        <div class="p-6 space-y-3" x-data="{ showPass: false }">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700">🔒 Cambiar Contraseña (Opcional)</span>
                                <button type="button" @click="showPass = !showPass" class="text-[11px] font-bold text-blue-900 hover:underline">
                                    <span x-text="showPass ? 'Ocultar campos' : 'Modificar contraseña'"></span>
                                </button>
                            </div>

                            <div x-show="showPass" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Actual</label>
                                    <input type="password" name="current_password" placeholder="••••••••" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nueva</label>
                                    <input type="password" name="password" placeholder="Mínimo 6" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Confirmar</label>
                                    <input type="password" name="password_confirmation" placeholder="Repite" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <a href="{{ route('profile.edit') }}" class="text-[11px] text-blue-900 font-bold hover:underline">
                                Abrir editor en página completa &rarr;
                            </a>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="editModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 transition">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-5 py-2 bg-blue-900 hover:bg-blue-950 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center space-x-1.5">
                                    <span>Guardar Cambios</span>
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endif

</div>

<script>
function profileFormManager(initialUser) {
    return {
        name: initialUser?.name || '',
        email: initialUser?.email || '',
        avatarColor: initialUser?.avatar_color || '#1e3a8a',
        country: initialUser?.country || 'Venezuela',
        state: initialUser?.state || '',
        city: initialUser?.city || '',
        bio: initialUser?.bio || '',
        customState: '',
        customCity: '',
        colorPalette: [
            { hex: '#1e3a8a', name: 'Azul Marino' },
            { hex: '#b91c1c', name: 'Rojo Carmesí' },
            { hex: '#047857', name: 'Verde Esmeralda' },
            { hex: '#7c3aed', name: 'Púrpura' },
            { hex: '#d97706', name: 'Ámbar' },
            { hex: '#0284c7', name: 'Azul Cielo' },
            { hex: '#be185d', name: 'Rosa Fuerte' },
            { hex: '#0f172a', name: 'Negro Azabache' }
        ],
        venezuelaData: {
            'Amazonas': ['Puerto Ayacucho', 'San Fernando de Atabapo', 'Maroa', 'Puerto Páez', 'San Carlos de Río Negro'],
            'Anzoátegui': ['Barcelona', 'Puerto La Cruz', 'Lechería', 'Guanta', 'El Tigre', 'Anaco', 'Cantaura', 'Pariaguán', 'Clarines', 'Píritu'],
            'Apure': ['San Fernando de Apure', 'Achaguas', 'Guasdualito', 'Biruaca', 'Elorza'],
            'Aragua': ['Maracay', 'Turmero', 'La Victoria', 'Cagua', 'Villa de Cura', 'El Limón', 'Palo Negro', 'San Mateo', 'Santa Rita'],
            'Barinas': ['Barinas', 'Barinitas', 'Socopó', 'Santa Bárbara', 'Ciudad Bolivia', 'Sabaneta'],
            'Bolívar': ['Ciudad Guayana (Puerto Ordaz)', 'Ciudad Bolívar', 'Upata', 'Caicara del Orinoco', 'Tumeremo', 'Guasipati', 'Santa Elena de Uairén'],
            'Carabobo': ['Valencia', 'Puerto Cabello', 'Naguanagua', 'San Diego', 'Guacara', 'Los Guayos', 'Mariara', 'Bejuma', 'Morón', 'San Joaquín'],
            'Cojedes': ['San Carlos', 'Tinaquillo', 'Tinaco', 'El Baúl', 'El Pao'],
            'Delta Amacuro': ['Tucupita', 'Pedernales', 'Curiapo'],
            'Distrito Capital': ['Caracas'],
            'Falcón': ['Coro', 'Punto Fijo', 'Chichiriviche', 'Tucacas', 'Dabajuro', 'Puerto Cumarebo', 'Churuguara'],
            'Guárico': ['San Juan de los Morros', 'Calabozo', 'Valle de la Pascua', 'Zaraza', 'Altagracia de Orituco', 'El Sombrero'],
            'La Guaira': ['La Guaira', 'Maiquetía', 'Catia La Mar', 'Caraballeda', 'Macuto', 'Naiguatá'],
            'Lara': ['Barquisimeto', 'Cabudare', 'Carora', 'El Tocuyo', 'Quíbor', 'Duaca', 'Sanare'],
            'Mérida': ['Mérida', 'El Vigía', 'Ejido', 'Tovar', 'Lagunillas', 'Mucuchíes', 'Bailadores'],
            'Miranda': ['Los Teques', 'Guarenas', 'Guatire', 'Petare', 'San Antonio de los Altos', 'Charallave', 'Cúa', 'Santa Teresa del Tuy', 'Ocumare del Tuy', 'Higuerote', 'Baruta', 'Chacao', 'El Hatillo'],
            'Monagas': ['Maturín', 'Punta de Mata', 'Caripe', 'Caripito', 'Temblador', 'Caicara de Maturín', 'Aragua de Maturín', 'Quiriquire'],
            'Nueva Esparta': ['Porlamar', 'Pampatar', 'La Asunción', 'Juan Griego', 'El Valle del Espíritu Santo', 'Punta de Piedras'],
            'Portuguesa': ['Guanare', 'Acarigua', 'Araure', 'Villa Bruzual (Turén)', 'Ospino', 'Biscucuy'],
            'Sucre': ['Cumaná', 'Carúpano', 'Güiria', 'Cariaco', 'Casanay', 'Río Caribe'],
            'Táchira': ['San Cristóbal', 'Táriba', 'Rubio', 'San Antonio del Táchira', 'La Fría', 'Colón', 'Palmira'],
            'Trujillo': ['Trujillo', 'Valera', 'Boconó', 'Carache', 'Betijoque', 'Sabana de Mendoza'],
            'Yaracuy': ['San Felipe', 'Yaritagua', 'Chivacoa', 'Nirgua', 'Cocorote'],
            'Zulia': ['Maracaibo', 'San Francisco', 'Cabimas', 'Ciudad Ojeda', 'Santa Bárbara del Zulia', 'Machiques', 'La Villa del Rosario', 'Los Puertos de Altagracia', 'Bachaquero']
        },
        countries: [
            'Venezuela', 'Argentina', 'Bolivia', 'Brasil', 'Chile', 'Colombia',
            'Costa Rica', 'Ecuador', 'El Salvador', 'España', 'Estados Unidos',
            'Guatemala', 'Honduras', 'México', 'Nicaragua', 'Panamá', 'Paraguay',
            'Perú', 'Puerto Rico', 'República Dominicana', 'Uruguay', 'Otro país'
        ],
        init() {
            if (this.country === 'Venezuela') {
                if (this.state && this.venezuelaData[this.state]) {
                    const cities = this.venezuelaData[this.state];
                    const matched = cities.find(c => c.toLowerCase() === (this.city || '').toLowerCase());
                    if (matched) {
                        this.city = matched;
                    } else if (this.city) {
                        this.customCity = this.city;
                        this.city = '__other__';
                    }
                }
            } else {
                this.customState = this.state;
                this.customCity = this.city;
            }
        },
        get statesList() {
            if (this.country === 'Venezuela') {
                return Object.keys(this.venezuelaData).sort((a, b) => a.localeCompare('es'));
            }
            return [];
        },
        get citiesList() {
            if (this.country === 'Venezuela' && this.state && this.venezuelaData[this.state]) {
                return this.venezuelaData[this.state];
            }
            return [];
        },
        onCountryChange() {
            this.state = '';
            this.city = '';
            this.customState = '';
            this.customCity = '';
        },
        onStateChange() {
            this.city = '';
            this.customCity = '';
        },
        get finalState() {
            return this.country === 'Venezuela' ? this.state : this.customState;
        },
        get finalCity() {
            if (this.country === 'Venezuela') {
                return this.city === '__other__' ? this.customCity : this.city;
            }
            return this.customCity;
        }
    };
}
</script>
@endsection
