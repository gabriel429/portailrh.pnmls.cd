<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portail RH PNMLS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-pnmls.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0077B5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PNMLS RH">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app"></div>

    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(reg => {
            setInterval(() => reg.update(), 86400000);
            reg.addEventListener('updatefound', () => {
                const newWorker = reg.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                        window.location.reload();
                    }
                });
            });
        }).catch(err => console.warn('SW registration failed:', err));
    }
    </script>
</body>
</html>
