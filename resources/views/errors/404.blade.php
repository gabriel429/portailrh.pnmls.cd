<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page introuvable | E-PNMLS</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/pnmls-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/pnmls-16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0077B5">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/pnmls-180.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', 'Roboto', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #f0f4f8 0%, #e2e8f0 50%, #dbeafe 100%);
            padding: 2rem;
            overflow: hidden;
            position: relative;
        }

        /* Decorative background shapes */
        body::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(0, 119, 181, .06);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(0, 119, 181, .04);
            pointer-events: none;
        }

        .error-container {
            text-align: center;
            max-width: 560px;
            position: relative;
            z-index: 1;
        }

        /* Animated 404 number */
        .error-code {
            font-size: 9rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: .5rem;
            position: relative;
            display: inline-block;
        }
        .error-code .digit {
            display: inline-block;
            background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: float 3s ease-in-out infinite;
        }
        .error-code .digit:nth-child(1) { animation-delay: 0s; }
        .error-code .digit:nth-child(2) { animation-delay: .3s; }
        .error-code .digit:nth-child(3) { animation-delay: .6s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Illustration */
        .error-illustration {
            margin: 1rem 0 1.5rem;
            position: relative;
        }
        .error-illustration .compass {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 12px 40px rgba(0, 119, 181, .2);
            position: relative;
            border: 3px solid rgba(0, 119, 181, .1);
        }
        .error-illustration .compass img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            animation: spin-logo 6s ease-in-out infinite;
        }
        @keyframes spin-logo {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-illustration .compass::after {
            content: '';
            position: absolute;
            width: 130px;
            height: 130px;
            border: 2px dashed rgba(0, 119, 181, .15);
            border-radius: 50%;
            animation: pulse-ring 3s ease-in-out infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: .5; }
        }

        /* Text */
        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: .5rem;
        }
        .error-message {
            font-size: .92rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* Action buttons */
        .error-actions {
            display: flex;
            gap: .8rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .btn-home {
            padding: .7rem 1.8rem;
            border: none;
            border-radius: 12px;
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: all .25s;
            cursor: pointer;
        }
        .btn-home.primary {
            background: linear-gradient(135deg, #0077B5 0%, #005a87 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(0, 119, 181, .25);
        }
        .btn-home.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 119, 181, .35);
            color: #fff;
        }
        .btn-home.secondary {
            background: #fff;
            color: #0077B5;
            border: 2px solid #e5e7eb;
        }
        .btn-home.secondary:hover {
            border-color: #0077B5;
            background: #f0f9ff;
            transform: translateY(-2px);
            color: #0077B5;
        }

        /* Quick links */
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            justify-content: center;
        }
        .quick-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-size: .78rem;
            color: #64748b;
            text-decoration: none;
            transition: all .2s;
        }
        .quick-link:hover {
            border-color: #0077B5;
            color: #0077B5;
            background: #f0f9ff;
            transform: translateY(-1px);
        }
        .quick-link i {
            font-size: .7rem;
            opacity: .7;
        }

        /* Footer */
        .error-footer {
            margin-top: 2.5rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            opacity: .5;
        }
        .error-footer img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }
        .error-footer span {
            font-size: .72rem;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .error-code { font-size: 6rem; }
            .error-title { font-size: 1.2rem; }
            .error-message { font-size: .85rem; }
            .error-actions { flex-direction: column; align-items: center; }
            .btn-home { width: 100%; justify-content: center; }
            .error-illustration .compass { width: 85px; height: 85px; }
            .error-illustration .compass img { width: 55px; height: 55px; }
        }
    </style>
</head>
<body>

    <div class="error-container">
        <div class="error-code">
            <span class="digit">4</span><span class="digit">0</span><span class="digit">4</span>
        </div>

        <div class="error-illustration">
            <div class="compass">
                <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS">
            </div>
        </div>

        <h1 class="error-title">Page introuvable</h1>
        <p class="error-message">
            La page que vous recherchez n'existe pas ou a été déplacée.<br>
            Vérifiez l'adresse ou utilisez les liens ci-dessous pour naviguer.
        </p>

        <div class="error-actions">
            <a href="{{ url('/dashboard') }}" class="btn-home primary">
                <i class="fas fa-home"></i> Retour au tableau de bord
            </a>
            <a href="javascript:history.back()" class="btn-home secondary">
                <i class="fas fa-arrow-left"></i> Page précédente
            </a>
        </div>

        <div class="quick-links">
            <a href="{{ url('/requests') }}" class="quick-link">
                <i class="fas fa-paper-plane"></i> Mes demandes
            </a>
            <a href="{{ url('/documents-travail') }}" class="quick-link">
                <i class="fas fa-folder-open"></i> Documents
            </a>
            <a href="{{ url('/profile') }}" class="quick-link">
                <i class="fas fa-user"></i> Mon profil
            </a>
            <a href="{{ url('/login') }}" class="quick-link">
                <i class="fas fa-sign-in-alt"></i> Connexion
            </a>
        </div>

        <div class="error-footer">
            <img src="{{ asset('images/logo-pnmls.png') }}" alt="PNMLS">
            <span>&copy; {{ date('Y') }} PNMLS &mdash; E-PNMLS</span>
        </div>
    </div>

</body>
</html>
