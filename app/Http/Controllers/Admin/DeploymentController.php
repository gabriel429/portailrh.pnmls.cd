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

            // Step 1: Run migrations (only organes table if it doesn't exist)
            $output_messages[] = "📦 Étape 1: Vérification de la table organes...";

            if (!Schema::hasTable('organes')) {
                $output_messages[] = "   Table non trouvée, création en cours...";

                ob_start();
                $exitCode = Artisan::call('migrate', ['--force' => true]);
                $migrationOutput = ob_get_clean();

                if ($exitCode === 0 || str_contains($migrationOutput, 'Nothing to migrate')) {
                    $output_messages[] = "✅ Table organes créée!";
                } else {
                    $error_messages[] = "❌ Erreur lors de la création de la table. Code: $exitCode";
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "✅ Table organes existe déjà";
            }

            // Step 2: Seed Organes (only if empty)
            $output_messages[] = "🌱 Étape 2: Insertion des données (SEN, SEP, SEL)...";

            $existingCount = \App\Models\Organe::count();
            if ($existingCount === 0) {
                $output_messages[] = "   Données non trouvées, création en cours...";

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
            } else {
                $output_messages[] = "✅ Les organes existent déjà ($existingCount enregistrements)";
            }

            // Step 3: Verify
            $output_messages[] = "✔️  Étape 3: Vérification finale...";

            if (Schema::hasTable('organes')) {
                $organeCount = \App\Models\Organe::count();
                $output_messages[] = "✅ Table organes existe avec $organeCount enregistrements";

                if ($organeCount === 3) {
                    $output_messages[] = "✅ Tous les organes présents (SEN, SEP, SEL)!";
                    $success = true;
                } elseif ($organeCount > 0) {
                    $output_messages[] = "⚠️  Attention: Trouvé $organeCount organes (attendu 3)";
                    $success = true; // Partial success
                } else {
                    $error_messages[] = "⚠️  Aucun organe trouvé";
                }
            } else {
                $error_messages[] = "❌ La table organes n'existe pas";
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
