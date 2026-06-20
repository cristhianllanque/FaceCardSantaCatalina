@extends('layouts.app')
@section('title', 'Iniciar Sesión')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
            <h2>FaceCard V2</h2>
            <p>Sistema de Reconocimiento Facial</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@facecard.com" required autofocus>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="remember" id="remember" style="accent-color:var(--accent);">
                <label for="remember" style="font-size:0.85rem;color:var(--text-secondary);cursor:pointer;">Recordarme</label>
            </div>
            <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content:center;">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="auth-footer">
            ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
        </div>
    </div>
</div>
@endsection
