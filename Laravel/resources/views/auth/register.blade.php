@extends('layouts.app')
@section('title', 'Registro')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
            <h2>Crear Cuenta</h2>
            <p>Regístrate en el sistema FaceCard</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Tu nombre" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repetir contraseña" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Rol</label>
                <select name="role" class="form-control" id="registerRole" required>
                    <option value="docente">Docente</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-group" id="areaGroup">
                <label class="form-label">Área</label>
                <input type="text" name="area" class="form-control" placeholder="Ej: Ingeniería de Sistemas" value="{{ old('area') }}">
                <p class="form-hint">Requerido para docentes</p>
            </div>
            <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content:center;">
                <i class="fas fa-user-plus"></i> Crear Cuenta
            </button>
        </form>

        <div class="auth-footer">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
        </div>
    </div>
</div>

<script>
document.getElementById('registerRole').addEventListener('change', function() {
    document.getElementById('areaGroup').style.display = this.value === 'docente' ? 'block' : 'none';
});
</script>
@endsection
