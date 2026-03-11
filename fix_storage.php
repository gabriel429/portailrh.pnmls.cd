<?php
/**
 * Fix storage link for shared hosting (Hostinger)
 * Creates proper storage access when symlinks don't work
 */

echo "<h2>Fix Storage Link</h2>";

// Vérifier les chemins
$storagePath = __DIR__ . '/storage/app/public';
$publicStoragePath = __DIR__ . '/public/storage';
$rootStoragePath = __DIR__ . '/storage_link';

echo "<h3>1. Vérification des chemins</h3>";
echo "Storage path: $storagePath → " . (is_dir($storagePath) ? '✅' : '❌') . "<br>";
echo "Public storage link: $publicStoragePath → " . (file_exists($publicStoragePath) ? '✅' : '❌') . "<br>";

// Créer le dossier storage/app/public s'il n'existe pas
if (!is_dir($storagePath)) {
    @mkdir($storagePath, 0755, true);
    @mkdir($storagePath . '/profiles', 0755, true);
    echo "✅ Dossier storage/app/public créé<br>";
}

// Vérifier si le lien symbolique existe dans public/
echo "<h3>2. Fix symlink dans public/</h3>";
if (is_link($publicStoragePath)) {
    echo "✅ Symlink existe déjà dans public/storage<br>";
} else if (is_dir($publicStoragePath)) {
    echo "✅ Dossier physique existe dans public/storage<br>";
} else {
    // Essayer de créer le symlink
    $target = __DIR__ . '/storage/app/public';
    $result = @symlink($target, $publicStoragePath);
    if ($result) {
        echo "✅ Symlink créé: public/storage → storage/app/public<br>";
    } else {
        echo "⚠️ Symlink impossible. Création du dossier physique...<br>";
        @mkdir($publicStoragePath, 0755, true);

        // Copier les fichiers existants
        $source = __DIR__ . '/storage/app/public';
        if (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $targetPath = $publicStoragePath . '/' . $iterator->getSubPathName();
                if ($item->isDir()) {
                    @mkdir($targetPath, 0755, true);
                } else {
                    copy($item, $targetPath);
                }
            }
            echo "✅ Fichiers copiés vers public/storage/<br>";
        }
    }
}

// Lister les fichiers dans storage
echo "<h3>3. Fichiers dans storage/app/public/</h3>";
if (is_dir($storagePath)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $count = 0;
    foreach ($files as $file) {
        echo "- " . str_replace($storagePath . '/', '', $file->getPathname()) . "<br>";
        $count++;
    }
    if ($count === 0) echo "Aucun fichier<br>";
} else {
    echo "Dossier n'existe pas<br>";
}

echo "<h3>4. Fichiers dans public/storage/</h3>";
if (is_dir($publicStoragePath)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($publicStoragePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $count = 0;
    foreach ($files as $file) {
        echo "- " . str_replace($publicStoragePath . '/', '', $file->getPathname()) . "<br>";
        $count++;
    }
    if ($count === 0) echo "Aucun fichier<br>";
} else {
    echo "Dossier n'existe pas<br>";
}

echo "<hr>";
echo "<p><a href='https://" . $_SERVER['HTTP_HOST'] . "/storage/profiles/' target='_blank'>Tester accès /storage/profiles/</a></p>";
echo "<p><a href='https://" . $_SERVER['HTTP_HOST'] . "/dashboard' target='_blank'>Retour au dashboard</a></p>";
?>
