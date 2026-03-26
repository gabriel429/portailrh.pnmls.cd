<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self' https:; font-src 'self';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="geolocation=(), microphone=(), camera=()">

    <title>Portail RH PNMLS</title>

    <!-- PWA Icons & Manifest -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/pnmls-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/pnmls-16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/pnmls-180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0077B5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PNMLS RH">
    <meta name="application-name" content="PNMLS RH">
    <meta name="description" content="Portail Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida">

    @vite('resources/js/app.js')
</head>
<body>
    <div id="app"></div>
    <noscript>
        <div style="text-align:center; padding:50px; font-family:Arial;">
            <h2>JavaScript requis</h2>
            <p>Cette application nécessite JavaScript pour fonctionner.</p>
        </div>
    </noscript>
</body>
</html>
