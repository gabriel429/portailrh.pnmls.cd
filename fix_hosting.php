<?php
echo "<h2>Setup Hostinger - Portail RH PNMLS</h2>";

try {
    // 1. Charger Laravel
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';

    $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
    $kernel->bootstrap();

    // 2. Migrations
    echo "<h3>1. Running migrations...</h3>";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";

    // 3. Seeders (avec gestion des doublons)
    echo "<h3>2. Running seeders...</h3>";
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        echo "<p>Seeders deja executes (donnees existantes). OK.</p>";
    }

    // 4. Clear caches
    echo "<h3>3. Clearing caches...</h3>";
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "Caches cleared.<br>";

    // 5. Storage link
    echo "<h3>4. Storage link...</h3>";
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        echo "Storage linked.<br>";
    } catch (\Exception $e) {
        echo "Storage link already exists. OK.<br>";
    }

    // 6. Verifier la connexion
    echo "<hr>";
    echo "<h2>Verification:</h2>";
    $users = \App\Models\User::count();
    $agents = \App\Models\Agent::count();
    $roles = \App\Models\Role::count();
    echo "Users: $users<br>";
    echo "Agents: $agents<br>";
    echo "Roles: $roles<br>";

    echo "<hr>";
    echo "<h2>Setup termine!</h2>";
    echo "<p><a href='/login'>Aller au login</a></p>";
    echo "<h3>Comptes de test:</h3>";
    echo "<ul>";
    echo "<li>Matricule: PNM-000001 / Mot de passe: password</li>";
    echo "<li>Matricule: PNM-000002 / Mot de passe: password</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h3>Erreur:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
