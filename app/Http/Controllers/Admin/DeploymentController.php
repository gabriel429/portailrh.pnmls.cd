<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class DeploymentController extends Controller
{
    /**
     * Show deployment page
     */
    public function index()
    {
        return view('admin.deployment');
    }

    /**
     * Execute Organe deployment (migrations + seeding)
     */
    public function deployOrganes()
    {
        $output = [];
        $errors = [];
        $success = false;

        try {
            $output[] = "🚀 Début du déploiement du module Organe...";

            // Step 1: Run migrations
            $output[] = "📦 Étape 1: Exécution des migrations...";

            ob_start();
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = ob_get_clean();

            if ($exitCode === 0) {
                $output[] = "✅ Migrations exécutées avec succès!";
            } else {
                $errors[] = "❌ Les migrations ont échoué. Code: $exitCode";
                return redirect()->route('admin.deployment.index')
                    ->with('errors', $errors)
                    ->with('output', $output);
            }

            // Step 2: Seed Organes
            $output[] = "🌱 Étape 2: Insertion des données (SEN, SEP, SEL)...";

            ob_start();
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'OrganeSeeder',
                '--force' => true,
            ]);
            $seedOutput = ob_get_clean();

            if ($exitCode === 0) {
                $output[] = "✅ Données insérées avec succès!";
            } else {
                $errors[] = "❌ Le seeding a échoué. Code: $exitCode";
                return redirect()->route('admin.deployment.index')
                    ->with('errors', $errors)
                    ->with('output', $output);
            }

            // Step 3: Verify
            $output[] = "✔️  Étape 3: Vérification...";

            if (Schema::hasTable('organes')) {
                $organeCount = \App\Models\Organe::count();
                $output[] = "✅ Table organes créée avec $organeCount enregistrements";

                if ($organeCount === 3) {
                    $output[] = "✅ Tous les organes créés (SEN, SEP, SEL)!";
                    $success = true;
                } else {
                    $errors[] = "⚠️  Attention: Attendu 3 organes, trouvé $organeCount";
                }
            } else {
                $errors[] = "❌ La table organes n'a pas été créée";
            }

            $output[] = "✨ Déploiement terminé!";

        } catch (\Exception $e) {
            $errors[] = "❌ ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output', $output)
            ->with('errors', $errors)
            ->with('success', $success);
    }
}
