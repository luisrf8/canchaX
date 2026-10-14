@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="profileFormManager(@json($user))">

    <!-- Breadcrumb & Title -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('landing') }}" class="hover:text-slate-900 transition">Inicio</a>
                <span>&rsaquo;</span>
                <a href="{{ route('organizers.show', $user->id) }}" class="hover:text-slate-900 transition">Mi Perfil</a>
                <span>&rsaquo;</span>
                <span class="text-slate-900">Editar Perfil</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Editar Mi Perfil</h1>
            <p class="text-xs sm:text-sm text-slate-500">Actualiza tu nombre, avatar, ubicación geográfica y biografía de organizador.</p>
        </div>

        <a href="{{ route('organizers.show', $user->id) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Ver Mi Perfil Público</span>
        </a>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <strong class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Por favor corrige los siguientes errores:
            </strong>
            <ul class="list-disc list-inside mt-2 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        
        <form method="POST" action="{{ route('profile.update') }}" @submit="syncLocationValues()" class="divide-y divide-slate-100">
            @csrf

            <!-- Live Avatar & Basic Identity Section -->
            <div class="p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <!-- Live Avatar Preview -->
                    <div class="relative group">
                        <div class="w-24 h-24 rounded-2xl text-white font-black text-4xl flex items-center justify-center shadow-lg transition-transform transform group-hover:scale-105 duration-200"
                             :style="{ backgroundColor: avatarColor }">
                            <span x-text="name ? name.trim().charAt(0).toUpperCase() : 'U'"></span>
                        </div>
                        <span class="absolute -bottom-2 -right-2 bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow border border-slate-700">
                            Vista previa
                        </span>
                    </div>

                    <!-- Identity Info & Avatar Color Pickers -->
                    <div class="flex-1 space-y-3 text-center sm:text-left">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900" x-text="name || 'Tu Nombre'"></h3>
                            <p class="text-xs text-slate-500 font-medium">
                                <span class="uppercase font-bold tracking-wider text-blue-900">{{ strtoupper($user->role) }}</span>
                                &bull; <span x-text="computedLocation"></span>
                            </p>
                        </div>

                        <!-- Color Presets -->
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                Color de tu Avatar
                            </label>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <template x-for="c in colorPalette" :key="c.hex">
                                    <button type="button" 
                                            @click="avatarColor = c.hex" 
                                            :style="{ backgroundColor: c.hex }"
                                            :title="c.name"
                                            :class="avatarColor.toLowerCase() === c.hex.toLowerCase() ? 'ring-3 ring-blue-900 ring-offset-2 scale-110' : 'opacity-85 hover:opacity-100 hover:scale-105'"
                                            class="w-7 h-7 rounded-xl transition duration-150 shadow-xs focus:outline-none">
                                    </button>
                                </template>
                                
                                <!-- Custom color input -->
                                <div class="flex items-center space-x-1 pl-2 border-l border-slate-200">
                                    <input type="color" x-model="avatarColor" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200 p-0.5" title="Elegir color personalizado">
                                    <span class="text-[10px] font-mono text-slate-500" x-text="avatarColor"></span>
                                </div>
                            </div>
                            <input type="hidden" name="avatar_color" :value="avatarColor">
                        </div>
                    </div>
                </div>

                <!-- Input Name & Email Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nombre y Apellido <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Correo Electrónico <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" x-model="email" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition">
                    </div>
                </div>
            </div>

            <!-- Location Section (País, Estado, Ciudad con Venezuela por defecto) -->
            <div class="p-6 sm:p-8 space-y-4 bg-slate-50/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
                            <span>📍 Ubicación Geográfica</span>
                        </h4>
                        <p class="text-[11px] text-slate-500">Se mostrará en tus torneos y perfil público para participantes y seguidores locales.</p>
                    </div>
                    <span class="text-xs font-bold text-blue-900 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200/60" x-text="country === 'Venezuela' ? '🇻🇪 Venezuela (Por Defecto)' : '🌍 ' + country"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- País -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">País</label>
                        <select x-model="country" @change="onCountryChange()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white shadow-2xs">
                            <template x-for="c in countries" :key="c">
                                <option :value="c" x-text="c" :selected="c === country"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Estado (Venezuela) -->
                    <div x-show="country === 'Venezuela'">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Estado</label>
                        <select x-model="state" @change="onStateChange()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white shadow-2xs">
                            <option value="">Selecciona estado...</option>
                            <template x-for="st in statesList" :key="st">
                                <option :value="st" x-text="st" :selected="st === state"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Ciudad (Venezuela) -->
                    <div x-show="country === 'Venezuela'">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Ciudad</label>
                        <select x-model="city" :disabled="!state" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white shadow-2xs disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="" x-text="!state ? 'Elige estado...' : 'Selecciona ciudad...'"></option>
                            <template x-for="ct in citiesList" :key="ct">
                                <option :value="ct" x-text="ct" :selected="ct === city"></option>
                            </template>
                            <option value="__other__" x-show="state">Otra ciudad / población...</option>
                        </select>
                    </div>

                    <!-- Estado (Internacional) -->
                    <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Estado / Región</label>
                        <input type="text" x-model="customState" placeholder="Ej. Antioquia, Buenos Aires..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white shadow-2xs">
                    </div>

                    <!-- Ciudad (Internacional) -->
                    <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Ciudad</label>
                        <input type="text" x-model="customCity" placeholder="Ej. Medellín, CABA..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white shadow-2xs">
                    </div>
                </div>

                <!-- Campo para especificar otra ciudad en Venezuela -->
                <div x-show="country === 'Venezuela' && city === '__other__'" x-cloak class="pt-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Especifica tu Población / Localidad en <span x-text="state"></span></label>
                    <input type="text" x-model="customCity" placeholder="Ej. Punta de Mata, Caripe, etc." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 bg-white">
                </div>

                <!-- Hidden inputs synced before submit -->
                <input type="hidden" name="country" :value="country">
                <input type="hidden" name="state" :value="finalState">
                <input type="hidden" name="city" :value="finalCity">
            </div>

            <!-- Bio Section -->
            <div class="p-6 sm:p-8 space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Biografía y Presentación
                </label>
                <textarea name="bio" rows="3" x-model="bio" placeholder="Describe brevemente tus torneos, experiencia organizando ligas deportivas, categorías que manejas..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition"></textarea>
                <p class="text-[11px] text-slate-400">Esta descripción aparecerá en el encabezado de tu página de perfil de organizador.</p>
            </div>

            <!-- Password Security Toggle Accordion -->
            <div class="p-6 sm:p-8 space-y-4" x-data="{ changePassword: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
                            <span>🔒 Seguridad y Contraseña</span>
                        </h4>
                        <p class="text-[11px] text-slate-500">Deja esto en blanco si no deseas cambiar tu contraseña actual.</p>
                    </div>
                    <button type="button" @click="changePassword = !changePassword" class="text-xs font-bold text-blue-900 hover:text-blue-950 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 transition">
                        <span x-text="changePassword ? 'Cancelar Cambio' : 'Cambiar Contraseña'"></span>
                    </button>
                </div>

                <div x-show="changePassword" x-collapse x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Contraseña Actual</label>
                        <input type="password" name="current_password" placeholder="••••••••" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-900 focus:border-slate-900 focus:ring-1">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nueva Contraseña</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-900 focus:border-slate-900 focus:ring-1">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" placeholder="Repite la contraseña" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-900 focus:border-slate-900 focus:ring-1">
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('organizers.show', $user->id) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-900 hover:bg-blue-950 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center space-x-1.5">
                    <span>Guardar Cambios</span>
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </div>

        </form>

    </div>

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
        },
        get computedLocation() {
            const parts = [this.finalCity, this.finalState, this.country].filter(Boolean);
            return parts.length ? parts.join(', ') : 'Venezuela';
        },
        syncLocationValues() {
            // Values are synced through hidden inputs
        }
    };
}
</script>
@endsection
