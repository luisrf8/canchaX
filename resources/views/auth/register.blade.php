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

        <form action="{{ route('register') }}" method="POST" class="space-y-5 text-xs">
            @csrf

            <!-- Role Selector Buttons -->
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-2">Selecciona tu Rol</label>
                <div class="grid grid-cols-2 gap-3" x-data="{ selectedRole: 'organizer' }">
                    <input type="hidden" name="role" :value="selectedRole">
                    
                    <button type="button" @click="selectedRole = 'organizer'" :class="selectedRole === 'organizer' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold transition space-y-1">
                        <span class="block text-xs font-extrabold">Organizador</span>
                        <span class="block text-[10px] opacity-75 font-normal">Crear y administrar torneos, llaves y mesas en vivo</span>
                    </button>

                    <button type="button" @click="selectedRole = 'participant'" :class="selectedRole === 'participant' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700 hover:border-slate-300'" class="p-3.5 rounded-xl border text-left font-bold transition space-y-1">
                        <span class="block text-xs font-extrabold">Participante / Atleta</span>
                        <span class="block text-[10px] opacity-75 font-normal">Seguir torneos, consultar partidos y clasificaciones</span>
                    </button>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo</label>
                <input type="text" name="name" required value="{{ old('name') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Ej. Carlos Mendoza">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                    <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="tu@correo.com">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Ciudad / Ubicación</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Madrid">
                </div>
            </div>

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

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Breve Biografía (Opcional)</label>
                <textarea name="bio" rows="2" class="w-full px-4 py-2 rounded-lg border border-slate-200 font-medium text-slate-900" placeholder="Cuéntanos brevemente sobre tus torneos o disciplina deportiva..."></textarea>
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
@endsection
