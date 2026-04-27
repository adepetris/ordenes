<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso | Ordenes</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-bg">
    <main class="auth-wrap">
        <section class="auth-card">
            <h1>Bienvenido</h1>
            <p>Ingresa para administrar ordenes de compra.</p>

            <form method="POST" action="{{ route('login.store') }}" class="form">
                @csrf

                <label for="username">Usuario</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus>
                @error('username')
                    <small class="error">{{ $message }}</small>
                @enderror

                <label for="password">Contrasena</label>
                <input id="password" type="password" name="password" required>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1"> Recordarme
                </label>

                <button type="submit" class="btn">Ingresar</button>
            </form>
        </section>
    </main>
</body>
</html>
