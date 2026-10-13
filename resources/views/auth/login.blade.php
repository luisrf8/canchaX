@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl border border-slate-200 shadow-xl">
        <div class="text-center space-y-2">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Iniciar Sesión en CanchaX</h2>
            <p class="text-xs text-slate-500">Ingresa con tus credenciales de Organizador o Participante.</p>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-xs font-semibold space-y-1">
                @foreach($errors->all() as $err)
                    <p>&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" data-loading-title="Iniciando Sesión..." data-loading-subtitle="Verificando credenciales de acceso..." class="space-y-5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="tu@correo.com">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Contraseña</label>
                <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-lg border border-slate-200 font-medium text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-slate-600 font-semibold">Recordarme</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-md transition">
                Iniciar Sesión &rarr;
            </button>
        </form>

        <div class="text-center pt-4 border-t border-slate-100 text-xs text-slate-500">
            ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-bold text-blue-900 hover:underline">Regístrate aquí</a>
        </div>
    </div>
</div>
@endsection
