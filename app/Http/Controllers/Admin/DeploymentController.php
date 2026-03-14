<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

            // Step 1: Create organes table if it doesn't exist
            $output_messages[] = "📦 Étape 1: Vérification de la table organes...";

            if (!Schema::hasTable('organes')) {
                $output_messages[] = "   Table non trouvée, création en cours...";

                try {
                    Schema::create('organes', function ($table) {
                        $table->id();
                        $table->string('code', 10)->unique();
                        $table->string('nom');
                        $table->string('sigle', 30)->nullable();
                        $table->text('description')->nullable();
                        $table->boolean('actif')->default(true);
                        $table->timestamps();
                    });

                    $output_messages[] = "✅ Table organes créée!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur lors de la création de la table: " . $e->getMessage();
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

                try {
                    \App\Models\Organe::create([
                        'code' => 'SEN',
                        'nom' => 'Secrétariat Exécutif National',
                        'sigle' => 'SEN',
                        'description' => 'Le niveau national du gouvernement',
                        'actif' => true,
                    ]);

                    \App\Models\Organe::create([
                        'code' => 'SEP',
                        'nom' => 'Secrétariat Exécutif Provincial',
                        'sigle' => 'SEP',
                        'description' => 'Le niveau provincial du gouvernement',
                        'actif' => true,
                    ]);

                    \App\Models\Organe::create([
                        'code' => 'SEL',
                        'nom' => 'Secrétariat Exécutif Local',
                        'sigle' => 'SEL',
                        'description' => 'Le niveau local du gouvernement',
                        'actif' => true,
                    ]);

                    $output_messages[] = "✅ Données insérées avec succès!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur lors de l'insertion des données: " . $e->getMessage();
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
