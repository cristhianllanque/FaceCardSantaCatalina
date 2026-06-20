<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FaceCard V2') — Sistema de Reconocimiento Facial</title>
    <meta name="description" content="Sistema de control de asistencia por reconocimiento facial">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
    @auth
    <div class="app-layout">
        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="logo-text">
                        <h1>FaceCard</h1>
                        <span>v2.0</span>
                    </div>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title">Principal</span>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                @if(auth()->user()->isAdmin())
                <div class="nav-section">
                    <span class="nav-section-title">Gestión</span>
                    <a href="{{ route('personas.index') }}" class="nav-item {{ request()->routeIs('personas.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Personas</span>
                    </a>
                    <a href="{{ route('personas.create') }}" class="nav-item {{ request()->routeIs('personas.create') ? 'active' : '' }}">
                        <i class="fas fa-user-plus"></i>
                        <span>Nuevo Registro</span>
                    </a>
                </div>
                @endif

                <div class="nav-section">
                    <span class="nav-section-title">Asistencia</span>
                    <a href="{{ route('asistencia.index') }}" class="nav-item {{ request()->routeIs('asistencia.index') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Historial</span>
                    </a>
                    <a href="{{ route('asistencia.envivo') }}" class="nav-item {{ request()->routeIs('asistencia.envivo') ? 'active' : '' }}">
                        <i class="fas fa-video"></i>
                        <span>En Vivo</span>
                        <span class="nav-badge pulse">LIVE</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-card">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ auth()->user()->name }}</span>
                        <span class="user-role">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout" title="Cerrar sesión">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="mobile-toggle" id="mobileToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="page-title">@yield('page-title', 'Dashboard')</h2>
                </div>
                <div class="top-bar-right">
                    <div class="clock" id="liveClock"></div>
                    <div class="top-bar-badge">
                        <i class="fas fa-circle text-success"></i>
                        <span>Sistema activo</span>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="alert alert-success" id="flashAlert">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger" id="flashAlert">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="content-area">
                @yield('content')
            </div>
        </main>
    </div>
    @else
        @yield('content')
    @endauth

    <script>
        // Live clock
        function updateClock() {
            const el = document.getElementById('liveClock');
            if (el) {
                const now = new Date();
                el.textContent = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileToggle = document.getElementById('mobileToggle');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Auto-dismiss flash alerts
        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) {
                alert.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);

        // CSRF token for fetch
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    </script>
    @stack('scripts')
</body>
</html>
