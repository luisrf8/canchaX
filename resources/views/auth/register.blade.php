@extends('layouts.app')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8 bg-white p-8 rounded-2xl border border-slate-200 shadow-xl">
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Crear una Cuenta en CanchaX</h2>
            <p class="text-xs text-slate-500">Regístrate como Organizador para publicar torneos o como Participante.</p>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-xs font-semibold space-y-1">
                @foreach($errors->all() as $err)
                    <p>&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" x-data="registrationForm()" @submit="syncFinalValues()" data-loading-title="Creando tu Cuenta..." data-loading-subtitle="Guardando tu ubicación y configurando tu perfil en CanchaX..." class="space-y-5 text-xs">
            @csrf

            <!-- Hidden Inputs for Ubicación & Rol -->
            <input type="hidden" name="role" :value="selectedRole">
            <input type="hidden" name="country" x-ref="countryInput" :value="country">
            <input type="hidden" name="state" x-ref="stateInput" :value="finalState">
            <input type="hidden" name="city" x-ref="cityInput" :value="finalCity">

            <!-- Role Selector Buttons -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-2">Selecciona tu Rol</label>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="selectedRole = 'organizer'" :class="selectedRole === 'organizer' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 bg-white'" class="p-3.5 rounded-xl border text-left font-bold transition space-y-1">
                        <span class="block text-xs font-extrabold">Organizador</span>
                        <span class="block text-[10px] opacity-75 font-normal">Crear y administrar torneos, llaves y mesas en vivo</span>
                    </button>

                    <button type="button" @click="selectedRole = 'participant'" :class="selectedRole === 'participant' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 bg-white'" class="p-3.5 rounded-xl border text-left font-bold transition space-y-1">
                        <span class="block text-xs font-extrabold">Participante / Atleta</span>
                        <span class="block text-[10px] opacity-75 font-normal">Seguir torneos, consultar partidos y clasificaciones</span>
                    </button>
                </div>
            </div>

            <!-- Nombre Completo -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo</label>
                <input type="text" name="name" required value="{{ old('name') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Ej. Luis Rojas">
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="tu@correo.com">
            </div>

            <!-- Ubicación Dinámica: País (Venezuela por defecto), Estado y Ciudad -->
            <div class="space-y-3 bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                <div class="flex items-center justify-between">
                    <span class="font-extrabold text-[11px] text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ubicación
                    </span>
                    <span class="text-[10px] text-slate-500 font-semibold" x-text="country === 'Venezuela' ? '🇻🇪 Venezuela (Por Defecto)' : '🌍 ' + country"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <!-- Selector de País -->
                    <div>
                        <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">País</label>
                        <select x-model="country" @change="onCountryChange()" class="w-full px-2.5 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white">
                            <template x-for="c in countries" :key="c">
                                <option :value="c" x-text="c" :selected="c === country"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Selector de Estado (Venezuela) -->
                    <div x-show="country === 'Venezuela'">
                        <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">Estado</label>
                        <select x-model="state" @change="onStateChange()" class="w-full px-2.5 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white">
                            <option value="">Selecciona estado...</option>
                            <template x-for="st in statesList" :key="st">
                                <option :value="st" x-text="st" :selected="st === state"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Selector de Ciudad (Venezuela) -->
                    <div x-show="country === 'Venezuela'">
                        <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">Ciudad</label>
                        <select x-model="city" :disabled="!state" class="w-full px-2.5 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="" x-text="!state ? 'Elige estado...' : 'Selecciona ciudad...'"></option>
                            <template x-for="ct in citiesList" :key="ct">
                                <option :value="ct" x-text="ct" :selected="ct === city"></option>
                            </template>
                            <option value="__other__" x-show="state">Otra ciudad / población...</option>
                        </select>
                    </div>

                    <!-- Estado (Internacional) -->
                    <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                        <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">Estado / Región</label>
                        <input type="text" x-model="customState" placeholder="Ej. Antioquia" class="w-full px-2.5 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white">
                    </div>

                    <!-- Ciudad (Internacional) -->
                    <div x-show="country !== 'Venezuela'" x-cloak class="sm:col-span-1">
                        <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">Ciudad</label>
                        <input type="text" x-model="customCity" placeholder="Ej. Medellín" class="w-full px-2.5 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white">
                    </div>
                </div>

                <!-- Campo para especificar otra ciudad en Venezuela -->
                <div x-show="country === 'Venezuela' && city === '__other__'" x-cloak class="pt-1">
                    <label class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[10px]">Especifica tu Ciudad o Población</label>
                    <input type="text" x-model="customCity" placeholder="Ej. Caripe, Punta de Mata, etc." class="w-full px-3 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs bg-white">
                </div>
            </div>

            <!-- Contraseñas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Contraseña</label>
                    <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="••••••••">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" required class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="••••••••">
                </div>
            </div>

            <!-- Breve Biografía -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Breve Biografía (Opcional)</label>
                <textarea name="bio" rows="2" class="w-full px-4 py-2 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Cuéntanos brevemente sobre tus torneos o disciplina deportiva...">{{ old('bio') }}</textarea>
            </div>

            <button type="submit" class="w-full py-3 bg-blue-900 hover:bg-blue-950 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                ¡Registrarse y Comenzar!
            </button>
        </form>

        <div class="text-center pt-4 border-t border-slate-100 text-xs text-slate-500">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-bold text-slate-900 hover:underline">Inicia sesión</a>
        </div>
    </div>
</div>

<script>
function registrationForm() {
    return {
        selectedRole: @json(old('role', 'organizer')),
        country: @json(old('country', 'Venezuela')),
        state: @json(old('state', '')),
        city: @json(old('city', '')),
        customCity: '',
        customState: '',
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
                    // Check if city is in list or custom
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
        syncFinalValues() {
            if (this.$refs.countryInput) this.$refs.countryInput.value = this.country;
            if (this.$refs.stateInput) this.$refs.stateInput.value = this.finalState;
            if (this.$refs.cityInput) this.$refs.cityInput.value = this.finalCity;
        }
    };
}
</script>
@endsection
