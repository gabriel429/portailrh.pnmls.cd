<?php
/**
 * DIAGNOSTIC MANIFEST - Vérifier quel manifest Laravel utilise
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DIAGNOSTIC MANIFEST LARAVEL - " . date('H:i:s') . "\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Chemins possibles du manifest
$manifests = [
    'Laravel recherche' => '/public/build/manifest.json',
    'Build local' => '/build/manifest.json',
    'Public build' => '/public/build/manifest.json'
];

foreach ($manifests as $label => $path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;

    echo "📁 {$label}:\n";
    echo "   Path: {$fullPath}\n";

    if (file_exists($fullPath)) {
        $size = number_format(filesize($fullPath) / 1024, 1);
        $modified = date('Y-m-d H:i:s', filemtime($fullPath));

        echo "   ✅ EXISTS ({$size} KB) - Modifié: {$modified}\n";

        // Lire le contenu du manifest
        $content = file_get_contents($fullPath);
        $manifest = json_decode($content, true);

        if ($manifest) {
            echo "   📋 Contenu du manifest:\n";
            foreach ($manifest as $key => $value) {
                if (is_array($value)) {
                    echo "      {$key}:\n";
                    if (isset($value['file'])) {
                        echo "         file: {$value['file']}\n";
                    }
                    if (isset($value['css'])) {
                        echo "         css: " . implode(', ', $value['css']) . "\n";
                    }
                } else {
                    echo "      {$key}: {$value}\n";
                }

                // Spécialement regarder resources/js/app.js
                if ($key === 'resources/js/app.js') {
                    echo "   🎯 FICHIER JS PRINCIPAL: " . $value['file'] . "\n";
                    if (strpos($value['file'], 'app-Bi-CZn0p.js') !== false) {
                        echo "   ❌ PROBLÈME: Utilise encore l'ANCIEN fichier!\n";
                    } elseif (strpos($value['file'], 'app--hCqS3r6.js') !== false) {
                        echo "   ✅ CORRECT: Utilise le NOUVEAU fichier!\n";
                    }
                }
            }
        } else {
            echo "   ❌ JSON INVALIDE ou VIDE\n";
        }
    } else {
        echo "   ❌ NOT FOUND\n";
    }
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

// Vérifier quels fichiers JS existent réellement
echo "📁 FICHIERS JS DÉTECTÉS:\n";
$jsPaths = [
    '/public/build/assets/app-Bi-CZn0p.js' => 'ANCIEN',
    '/public/build/assets/app--hCqS3r6.js' => 'NOUVEAU'
];

foreach ($jsPaths as $path => $type) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($fullPath)) {
        $size = number_format(filesize($fullPath) / 1024, 1);
        $modified = date('H:i:s', filemtime($fullPath));
        echo "   ✅ {$type}: " . basename($path) . " ({$size} KB) - {$modified}\n";
    } else {
        echo "   ❌ {$type}: " . basename($path) . " - NOT FOUND\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 SOLUTION:\n";
echo "Si le manifest pointe vers l'ANCIEN fichier:\n";
echo "1. Générer un nouveau build avec: npm run build\n";
echo "2. Uploader le nouveau manifest.json\n";
echo "3. Supprimer l'ancien app-Bi-CZn0p.js\n";
echo "\nOu utiliser le script de force-update pour corriger automatiquement.\n";
?>