<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Portail RH PNMLS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-pnmls.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0077B5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PNMLS RH">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', 'Roboto', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f0f2f5;
        }

        /* ── Left panel ── */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, rgba(0,119,181,.88) 0%, rgba(0,90,135,.92) 40%, rgba(0,63,92,.95) 100%),
                        url('{{ asset("images/pnmls.jpeg") }}') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            color: #fff;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
        }

        .login-brand {
            text-align: center;
            position: relative;
            z-index: 1;
            max-width: 420px;
        }
        .login-brand img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.2));
        }
        .login-brand h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: .5rem;
            letter-spacing: -.3px;
        }
        .login-brand p {
            font-size: .92rem;
            opacity: .8;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .login-features {
            list-style: none;
            padding: 0;
            text-align: left;
            position: relative;
            z-index: 1;
        }
        .login-features li {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .55rem 0;
            font-size: .88rem;
            opacity: .9;
        }
        .login-features li .feat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            flex-shrink: 0;
        }

        /* ── Right panel ── */
        .login-right {
            width: 520px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            background: #fff;
            position: relative;
        }

        .login-header {
            margin-bottom: 2rem;
        }
        .login-header .mobile-logo {
            display: none;
        }
        .login-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: .3rem;
        }
        .login-header p {
            font-size: .88rem;
            color: #9ca3af;
            margin: 0;
        }

        /* Form */
        .login-form .form-group {
            margin-bottom: 1.3rem;
        }
        .login-form label {
            font-size: .82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .4rem;
            display: block;
        }
        .login-form .input-wrapper {
            position: relative;
        }
        .login-form .input-wrapper i.field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: .9rem;
            z-index: 2;
            transition: color .2s;
        }
        .login-form .form-control {
            height: 48px;
            padding-left: 42px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: .9rem;
            transition: all .2s;
            background: #fafbfc;
        }
        .login-form .form-control:focus {
            border-color: #0077B5;
            box-shadow: 0 0 0 3px rgba(0,119,181,.1);
            background: #fff;
        }
        .login-form .form-control:focus + i.field-icon,
        .login-form .input-wrapper:focus-within i.field-icon {
            color: #0077B5;
        }
        .login-form .form-control.is-invalid {
            border-color: #ef4444;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            z-index: 2;
            transition: color .2s;
        }
        .password-toggle:hover { color: #0077B5; }

        .login-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .login-extras .form-check-input:checked {
            background-color: #0077B5;
            border-color: #0077B5;
        }
        .login-extras .form-check-label {
            font-size: .82rem;
            color: #6b7280;
        }
        .login-extras .forgot-link {
            font-size: .82rem;
            color: #0077B5;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s;
        }
        .login-extras .forgot-link:hover {
            color: #005a87;
        }

        .btn-login {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 12px;
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
            cursor: pointer;
            transition: all .25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            box-shadow: 0 4px 14px rgba(0,119,181,.25);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,119,181,.35);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        /* Error alert */
        .login-alert {
            padding: .75rem 1rem;
            border-radius: 10px;
            font-size: .82rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .login-alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* Footer */
        .login-footer {
            margin-top: 2rem;
            padding-top: 1.2rem;
            border-top: 1px solid #f3f4f6;
        }
        .login-footer .support-card {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: .8rem 1rem;
            border-radius: 10px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
        }
        .login-footer .support-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #0077B5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
        }
        .login-footer .support-text {
            font-size: .78rem;
            color: #6b7280;
            line-height: 1.4;
        }
        .login-footer .support-text strong {
            color: #1e293b;
        }

        .login-copyright {
            text-align: center;
            font-size: .72rem;
            color: #d1d5db;
            margin-top: 1.2rem;
        }

        /* ── Responsive ── */
        @media (max-width: 991px) {
            body { flex-direction: column; }
            .login-left {
                padding: 2rem 1.5rem;
                min-height: auto;
            }
            .login-brand img { width: 80px; height: 80px; }
            .login-brand h1 { font-size: 1.3rem; }
            .login-brand p { margin-bottom: 1rem; font-size: .82rem; }
            .login-features { display: none; }
            .login-right {
                width: 100%;
                padding: 2rem 1.5rem;
                border-radius: 24px 24px 0 0;
                margin-top: -20px;
                position: relative;
                z-index: 2;
            }
        }
        @media (max-width: 576px) {
            .login-right { padding: 1.5rem 1.2rem; }
            .login-header h2 { font-size: 1.3rem; }
            .login-extras { flex-direction: column; align-items: flex-start; gap: .5rem; }
        }
    </style>
</head>
<body>

    {{-- ── Left panel ── --}}
    <div class="login-left">
        <div class="login-brand">
            <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS Logo">
            <h1>Portail RH PNMLS</h1>
            <p>Gestion Intégrée des Ressources Humaines<br>Programme National Multisectoriel de Lutte contre le Sida</p>
        </div>
        <ul class="login-features">
            <li>
                <span class="feat-icon"><i class="fas fa-users"></i></span>
                Gestion centralisée des agents SEN, SEP et SEL
            </li>
            <li>
                <span class="feat-icon"><i class="fas fa-folder-open"></i></span>
                Gestion documentaire et archivage numérique
            </li>
            <li>
                <span class="feat-icon"><i class="fas fa-paper-plane"></i></span>
                Workflow des demandes (congés, absences, formation)
            </li>
            <li>
                <span class="feat-icon"><i class="fas fa-chart-line"></i></span>
                Suivi de carrière et plans de travail
            </li>
            <li>
                <span class="feat-icon"><i class="fas fa-shield-alt"></i></span>
                Sécurisé et accessible par rôles
            </li>
        </ul>
    </div>

    {{-- ── Right panel (form) ── --}}
    <div class="login-right">
        <div class="login-header">
            <h2>Bienvenue</h2>
            <p>Connectez-vous à votre espace personnel</p>
        </div>

        @if($errors->any())
        <div class="login-alert login-alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            @foreach($errors->all() as $error)
                <span>{{ $error }}</span>
            @endforeach
        </div>
        @endif

        <form action="{{ url('/login') }}" method="POST" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email">Adresse email professionnelle</label>
                <div class="input-wrapper">
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           placeholder="prenom.nom@pnmls.cd"
                           value="{{ old('email') }}"
                           required
                           autofocus>
                    <i class="fas fa-envelope field-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="Entrez votre mot de passe"
                           required>
                    <i class="fas fa-lock field-icon"></i>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="login-extras">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <a href="{{ url('/forgot-password') }}" class="forgot-link">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right"></i> Se connecter
            </button>
        </form>

        <div class="login-footer">
            <div class="support-card">
                <div class="support-icon"><i class="fas fa-headset"></i></div>
                <div class="support-text">
                    <strong>Besoin d'aide ?</strong><br>
                    Contactez la Section Nouvelle Technologie du PNMLS
                </div>
            </div>
            <div class="login-copyright">
                &copy; {{ date('Y') }} PNMLS &mdash; Programme National Multisectoriel de Lutte contre le Sida
            </div>
        </div>
    </div>

    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(reg => {
            setInterval(() => reg.update(), 60000);
            reg.addEventListener('updatefound', () => {
                const nw = reg.installing;
                nw.addEventListener('statechange', () => {
                    if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                        location.reload();
                    }
                });
            });
        });
    }
    </script>
</body>
</html>
