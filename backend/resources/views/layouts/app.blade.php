<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ordenes de Compra')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            var savedTheme = null;
            try {
                savedTheme = localStorage.getItem('theme');
            } catch (error) {
                savedTheme = null;
            }

            var theme = savedTheme;
            if (!theme) {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <a href="{{ route('dashboard') }}" class="brand">Ordenes</a>
            @auth
                <div class="topbar-actions-wrap">
                    <nav class="nav-links">
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        <a href="{{ route('orders.index') }}" class="nav-link">Ordenes</a>
                        @if(auth()->user()->hasAnyRole(['administrador', 'usuario_autorizado']))
                            <a href="{{ route('approvals.index') }}" class="nav-link">Aprobaciones</a>
                        @endif
                        @if(auth()->user()->hasRole('administrador'))
                            <a href="{{ route('audit-logs.index') }}" class="nav-link">Auditoria</a>
                            <a href="{{ route('users.index') }}" class="nav-link">Usuarios</a>
                        @endif
                        <a href="{{ route('suppliers.index') }}" class="nav-link">Proveedores</a>
                    </nav>

                    <div class="topbar-actions">
                        <button type="button" class="theme-toggle" data-theme-toggle aria-label="Cambiar tema">Tema</button>
                        <span class="user">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline">Salir</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </header>

    <main class="container page">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <script>
        (function () {
            var root = document.documentElement;
            var toggle = document.querySelector('[data-theme-toggle]');

            if (!toggle) {
                return;
            }

            var setThemeLabel = function (theme) {
                toggle.textContent = theme === 'dark' ? 'Claro' : 'Oscuro';
            };

            var currentTheme = root.getAttribute('data-theme') || 'light';
            setThemeLabel(currentTheme);

            toggle.addEventListener('click', function () {
                var nextTheme = (root.getAttribute('data-theme') || 'light') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', nextTheme);
                setThemeLabel(nextTheme);

                try {
                    localStorage.setItem('theme', nextTheme);
                } catch (error) {
                    // ignore storage errors
                }
            });
        })();
    </script>
</body>
</html>
