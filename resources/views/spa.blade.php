<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>E-PNMLS</title>

    <!-- PWA Icons & Manifest -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/pnmls-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/pnmls-16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/pnmls-180.png') }}">
    @php
        $appManifestPath = public_path('manifest.json');
        $appManifestVersion = is_file($appManifestPath) ? filemtime($appManifestPath) : time();
    @endphp
    <link rel="manifest" href="{{ asset('manifest.json') }}?v={{ $appManifestVersion }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#006c9f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PNMLS RH">
    <meta name="application-name" content="PNMLS RH">
    <meta name="description" content="Portail Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida">

    @php
        $manifest = [];
        $viteEntry = null;
        $viteImports = [];
        $viteAsset = fn (string $path) => asset('public/build/' . ltrim($path, '/'));
        $pageCssFiles = [];
        $rhModernCssPath = public_path('css/rh-modern.css');
        $rhModernCssVersion = is_file($rhModernCssPath) ? filemtime($rhModernCssPath) : time();
        $manifestPath = public_path('build/manifest.json');
        $pageManifestEntries = [
            'resources/js/views/dashboard/DashboardView.vue',
            'resources/js/views/dashboard/RhDashboardView.vue',
        ];

        if (request()->is('taches*')) {
            $pageManifestEntries[] = 'resources/js/views/taches/TacheListView.vue';
        }

        if (! app()->isLocal() && is_file($manifestPath)) {
            $decodedManifest = json_decode((string) file_get_contents($manifestPath), true);
            $manifest = is_array($decodedManifest) ? $decodedManifest : [];
            $viteEntry = $manifest['resources/js/app.js'] ?? null;

            if (is_array($viteEntry)) {
                foreach (($viteEntry['imports'] ?? []) as $importKey) {
                    if (isset($manifest[$importKey]['file'])) {
                        $viteImports[] = $manifest[$importKey]['file'];
                    }
                }
            }

            if ($manifest !== []) {
                foreach ($pageManifestEntries as $entry) {
                    foreach (($manifest[$entry]['css'] ?? []) as $cssFile) {
                        $pageCssPath = public_path('build/' . ltrim($cssFile, '/'));
                        $pageCssFiles[$cssFile] = is_file($pageCssPath)
                            ? filemtime($pageCssPath)
                            : time();
                    }
                }
            }
        }
    @endphp
    @if (app()->isLocal() || ! is_array($viteEntry) || empty($viteEntry['file']))
    @vite('resources/js/app.js')
    @else
    @foreach (($viteEntry['css'] ?? []) as $cssFile)
    <link rel="preload" as="style" href="{{ $viteAsset($cssFile) }}" />
    @endforeach
    <link rel="modulepreload" as="script" href="{{ $viteAsset($viteEntry['file']) }}" />
    @foreach ($viteImports as $importFile)
    <link rel="modulepreload" as="script" href="{{ $viteAsset($importFile) }}" />
    @endforeach
    @foreach (($viteEntry['css'] ?? []) as $cssFile)
    <link rel="stylesheet" href="{{ $viteAsset($cssFile) }}" />
    @endforeach
    <script type="module" src="{{ $viteAsset($viteEntry['file']) }}"></script>
    @endif
    @foreach ($pageCssFiles as $pageCssFile => $pageCssVersion)
    <link rel="stylesheet" href="{{ $viteAsset($pageCssFile) }}?v={{ $pageCssVersion }}">
    @endforeach
    <link rel="stylesheet" href="{{ asset('css/rh-modern.css') }}?v={{ $rhModernCssVersion }}">
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
