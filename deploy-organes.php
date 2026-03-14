<?php
/**
 * Deploy Script - Organe Module Deployment
 *
 * Usage: php deploy-organes.php
 *
 * This script runs migrations and seeds the Organe data in production
 */

// Change to project root
chdir(__DIR__);

// Load Laravel
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Database\Console\Seed\SeedCommand;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║        Organe Module Deployment & Seeding Script           ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    // Step 1: Run migrations
    echo "📦 Step 1: Running database migrations...\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $exitCode = $kernel->call('migrate', [
        '--force' => true,
    ]);

    if ($exitCode === 0) {
        echo "✅ Migrations completed successfully!\n\n";
    } else {
        echo "❌ Migration failed with exit code: $exitCode\n";
        exit(1);
    }

    // Step 2: Seed Organes data
    echo "🌱 Step 2: Seeding Organe data (SEN, SEP, SEL)...\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $exitCode = $kernel->call('db:seed', [
        '--class' => 'OrganeSeeder',
        '--force' => true,
    ]);

    if ($exitCode === 0) {
        echo "✅ Seeding completed successfully!\n\n";
    } else {
        echo "❌ Seeding failed with exit code: $exitCode\n";
        exit(1);
    }

    // Step 3: Verify
    echo "✔️  Step 3: Verification...\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $organeCount = \App\Models\Organe::count();
    echo "✅ Organes in database: $organeCount\n";

    if ($organeCount === 3) {
        echo "✅ All 3 organes created successfully (SEN, SEP, SEL)!\n\n";
    } else {
        echo "⚠️  Warning: Expected 3 organes, found $organeCount\n\n";
    }

    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║            ✨ Deployment Completed Successfully!           ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\nYou can now access:\n";
    echo "  📊 Dashboard: /admin/parametres\n";
    echo "  🎛️  Organes CRUD: /admin/organes\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$kernel->terminate(0);
