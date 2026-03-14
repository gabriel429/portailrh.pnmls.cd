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
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Début du déploiement du module Organe...";

            // Step 1: Run migrations
            $output_messages[] = "📦 Étape 1: Exécution des migrations...";

            ob_start();
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = ob_get_clean();

            if ($exitCode === 0) {
                $output_messages[] = "✅ Migrations exécutées avec succès!";
            } else {
                $error_messages[] = "❌ Les migrations ont échoué. Code: $exitCode";
                return redirect()->route('admin.deployment.index')
                    ->with('error_messages', $error_messages)
                    ->with('output_messages', $output_messages);
            }

            // Step 2: Seed Organes
            $output_messages[] = "🌱 Étape 2: Insertion des données (SEN, SEP, SEL)...";

            ob_start();
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'OrganeSeeder',
                '--force' => true,
            ]);
            $seedOutput = ob_get_clean();

            if ($exitCode === 0) {
                $output_messages[] = "✅ Données insérées avec succès!";
            } else {
                $error_messages[] = "❌ Le seeding a échoué. Code: $exitCode";
                return redirect()->route('admin.deployment.index')
                    ->with('error_messages', $error_messages)
                    ->with('output_messages', $output_messages);
            }

            // Step 3: Verify
            $output_messages[] = "✔️  Étape 3: Vérification...";

            if (Schema::hasTable('organes')) {
                $organeCount = \App\Models\Organe::count();
                $output_messages[] = "✅ Table organes créée avec $organeCount enregistrements";

                if ($organeCount === 3) {
                    $output_messages[] = "✅ Tous les organes créés (SEN, SEP, SEL)!";
                    $success = true;
                } else {
                    $error_messages[] = "⚠️  Attention: Attendu 3 organes, trouvé $organeCount";
                }
            } else {
                $error_messages[] = "❌ La table organes n'a pas été créée";
            }

            $output_messages[] = "✨ Déploiement terminé!";

        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }
}
