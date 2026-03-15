<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\InstitutionCategorie;
use App\Models\Institution;

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

    /**
     * Execute Users system upgrade (add agent_id and role_id)
     */
    public function deployUsers()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Début de la mise à jour du système Utilisateurs...";

            // Step 1: Add columns to users table
            $output_messages[] = "📦 Étape 1: Vérification de la table users...";

            if (Schema::hasTable('users')) {
                $output_messages[] = "   Table users trouvée";

                try {
                    // Check if columns already exist
                    $hasAgentId = Schema::hasColumn('users', 'agent_id');
                    $hasRoleId = Schema::hasColumn('users', 'role_id');

                    if (!$hasAgentId || !$hasRoleId) {
                        $output_messages[] = "   Ajout des colonnes agent_id et role_id...";

                        Schema::table('users', function ($table) use ($hasAgentId, $hasRoleId) {
                            if (!$hasAgentId) {
                                $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('cascade')->after('id');
                            }
                            if (!$hasRoleId) {
                                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null')->after('agent_id');
                            }
                        });

                        $output_messages[] = "✅ Colonnes ajoutées avec succès!";
                    } else {
                        $output_messages[] = "✅ Les colonnes existent déjà";
                    }
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur lors de l'ajout des colonnes: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $error_messages[] = "❌ La table users n'existe pas";
                return redirect()->route('admin.deployment.index')
                    ->with('error_messages', $error_messages)
                    ->with('output_messages', $output_messages);
            }

            // Step 2: Verify structure
            $output_messages[] = "✔️  Étape 2: Vérification finale...";

            if (Schema::hasColumn('users', 'agent_id') && Schema::hasColumn('users', 'role_id')) {
                $output_messages[] = "✅ Colonnes agent_id et role_id présentes";
                $output_messages[] = "✅ Relations configurées: User → Agent, User → Role";
                $success = true;
            } else {
                $error_messages[] = "❌ Les colonnes n'ont pas pu être vérifiées";
            }

            $output_messages[] = "✨ Déploiement Utilisateurs terminé!";

        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }

    /**
     * Execute Institutions deployment (migrations + seeding)
     */
    public function deployInstitutions()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Début du déploiement du module Institutions...";

            // Step 1: Create institution_categories table
            $output_messages[] = "📦 Étape 1: Vérification de la table institution_categories...";

            if (!Schema::hasTable('institution_categories')) {
                $output_messages[] = "   Table non trouvée, création en cours...";

                try {
                    Schema::create('institution_categories', function ($table) {
                        $table->id();
                        $table->string('code')->unique();
                        $table->string('nom');
                        $table->tinyInteger('ordre')->default(1);
                        $table->text('description')->nullable();
                        $table->timestamps();
                        $table->index('ordre');
                    });

                    $output_messages[] = "✅ Table institution_categories créée!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur création institution_categories: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "✅ Table institution_categories existe déjà";
            }

            // Step 2: Create institutions table
            $output_messages[] = "📦 Étape 2: Vérification de la table institutions...";

            if (!Schema::hasTable('institutions')) {
                $output_messages[] = "   Table non trouvée, création en cours...";

                try {
                    Schema::create('institutions', function ($table) {
                        $table->id();
                        $table->string('code')->unique();
                        $table->string('nom');
                        $table->foreignId('institution_categorie_id')
                            ->constrained('institution_categories')
                            ->onDelete('cascade');
                        $table->tinyInteger('ordre')->default(1);
                        $table->text('description')->nullable();
                        $table->boolean('actif')->default(true);
                        $table->timestamps();
                        $table->index('institution_categorie_id');
                        $table->index('actif');
                        $table->index('ordre');
                    });

                    $output_messages[] = "✅ Table institutions créée!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur création institutions: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "✅ Table institutions existe déjà";
            }

            // Step 3: Add institution_id column to agents table
            $output_messages[] = "📦 Étape 3: Vérification de la colonne institution_id dans agents...";

            if (Schema::hasTable('agents')) {
                if (!Schema::hasColumn('agents', 'institution_id')) {
                    $output_messages[] = "   Colonne non trouvée, création en cours...";

                    try {
                        Schema::table('agents', function ($table) {
                            $table->foreignId('institution_id')
                                ->nullable()
                                ->constrained('institutions')
                                ->onDelete('set null')
                                ->after('province_id');
                        });

                        $output_messages[] = "✅ Colonne institution_id ajoutée!";
                    } catch (\Exception $e) {
                        $error_messages[] = "❌ Erreur ajout institution_id: " . $e->getMessage();
                        return redirect()->route('admin.deployment.index')
                            ->with('error_messages', $error_messages)
                            ->with('output_messages', $output_messages);
                    }
                } else {
                    $output_messages[] = "✅ Colonne institution_id existe déjà";
                }
            } else {
                $error_messages[] = "❌ La table agents n'existe pas";
                return redirect()->route('admin.deployment.index')
                    ->with('error_messages', $error_messages)
                    ->with('output_messages', $output_messages);
            }

            // Step 4: Seed institutions data
            $output_messages[] = "🌱 Étape 4: Insertion des données (11 catégories + ~70 institutions)...";

            $existingCatCount = InstitutionCategorie::count();
            if ($existingCatCount === 0) {
                $output_messages[] = "   Données non trouvées, création en cours...";

                try {
                    \Artisan::call('db:seed', ['--class' => 'InstitutionSeeder']);
                    $output_messages[] = "✅ Données insérées avec succès!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur lors du seeding: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "✅ Les institutions existent déjà ($existingCatCount catégories)";
            }

            // Step 5: Verify
            $output_messages[] = "✔️  Étape 5: Vérification finale...";

            if (Schema::hasTable('institution_categories') && Schema::hasTable('institutions')) {
                $catCount = InstitutionCategorie::count();
                $instCount = Institution::count();
                $output_messages[] = "✅ Tables existent avec $catCount catégories et $instCount institutions";

                if ($catCount === 11 && $instCount > 60) {
                    $output_messages[] = "✅ Toutes les données présentes!";
                    $success = true;
                } elseif ($catCount > 0 && $instCount > 0) {
                    $output_messages[] = "⚠️  Attention: Trouvé $catCount catégories et $instCount institutions";
                    $success = true; // Partial success
                } else {
                    $error_messages[] = "⚠️  Données manquantes";
                }
            } else {
                $error_messages[] = "❌ Les tables n'existent pas";
            }

            $output_messages[] = "✨ Déploiement Institutions terminé!";

        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }

    /**
     * Execute Messages deployment (create messages table)
     */
    public function deployMessages()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Début du déploiement du module Messages...";

            // Step 1: Create messages table if it doesn't exist
            $output_messages[] = "📦 Étape 1: Vérification de la table messages...";

            if (!Schema::hasTable('messages')) {
                $output_messages[] = "   Table non trouvée, création en cours...";

                try {
                    Schema::create('messages', function ($table) {
                        $table->id();
                        $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                        $table->string('sujet');
                        $table->text('contenu');
                        $table->boolean('lu')->default(false);
                        $table->timestamps();
                    });

                    $output_messages[] = "✅ Table messages créée!";
                } catch (\Exception $e) {
                    $error_messages[] = "❌ Erreur lors de la création de la table: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "✅ Table messages existe déjà";
            }

            // Step 2: Verify
            $output_messages[] = "✔️  Étape 2: Vérification finale...";

            if (Schema::hasTable('messages')) {
                $msgCount = DB::table('messages')->count();
                $output_messages[] = "✅ Table messages existe avec $msgCount enregistrements";
                $success = true;
            } else {
                $error_messages[] = "❌ La table messages n'existe pas";
            }

            $output_messages[] = "✨ Déploiement Messages terminé!";

        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }

    /**
     * Deploy Communiques module
     */
    public function deployCommuniques()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "Debut du deploiement du module Communiques...";

            if (!Schema::hasTable('communiques')) {
                $output_messages[] = "Table non trouvee, creation en cours...";

                try {
                    Schema::create('communiques', function ($table) {
                        $table->id();
                        $table->foreignId('auteur_id')->constrained('users')->onDelete('cascade');
                        $table->string('titre');
                        $table->text('contenu');
                        $table->enum('urgence', ['normal', 'important', 'urgent'])->default('normal');
                        $table->string('signataire')->nullable();
                        $table->date('date_expiration')->nullable();
                        $table->boolean('actif')->default(true);
                        $table->timestamps();
                    });

                    $output_messages[] = "Table communiques creee!";
                } catch (\Exception $e) {
                    $error_messages[] = "Erreur lors de la creation de la table: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "Table communiques existe deja";
            }

            if (Schema::hasTable('communiques')) {
                $count = DB::table('communiques')->count();
                $output_messages[] = "Table communiques existe avec $count enregistrements";
                $success = true;
            } else {
                $error_messages[] = "La table communiques n'existe pas";
            }

            $output_messages[] = "Deploiement Communiques termine!";

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }

    public function deployTaches()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "Debut du deploiement du module Taches...";

            // Table taches
            if (!Schema::hasTable('taches')) {
                $output_messages[] = "Creation de la table taches...";

                try {
                    Schema::create('taches', function ($table) {
                        $table->id();
                        $table->foreignId('createur_id')->constrained('agents')->onDelete('cascade');
                        $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                        $table->string('titre');
                        $table->text('description')->nullable();
                        $table->enum('priorite', ['normale', 'haute', 'urgente'])->default('normale');
                        $table->enum('statut', ['nouvelle', 'en_cours', 'terminee'])->default('nouvelle');
                        $table->date('date_echeance')->nullable();
                        $table->timestamps();
                        $table->index('createur_id');
                        $table->index('agent_id');
                        $table->index('statut');
                    });
                    $output_messages[] = "Table taches creee!";
                } catch (\Exception $e) {
                    $error_messages[] = "Erreur table taches: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "Table taches existe deja";
            }

            // Table tache_commentaires
            if (!Schema::hasTable('tache_commentaires')) {
                $output_messages[] = "Creation de la table tache_commentaires...";

                try {
                    Schema::create('tache_commentaires', function ($table) {
                        $table->id();
                        $table->foreignId('tache_id')->constrained('taches')->onDelete('cascade');
                        $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                        $table->text('contenu');
                        $table->string('ancien_statut')->nullable();
                        $table->string('nouveau_statut')->nullable();
                        $table->timestamps();
                        $table->index('tache_id');
                    });
                    $output_messages[] = "Table tache_commentaires creee!";
                } catch (\Exception $e) {
                    $error_messages[] = "Erreur table tache_commentaires: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "Table tache_commentaires existe deja";
            }

            // Verification
            if (Schema::hasTable('taches') && Schema::hasTable('tache_commentaires')) {
                $count = DB::table('taches')->count();
                $output_messages[] = "Tables taches ($count enregistrements) et tache_commentaires existent";
                $success = true;
            } else {
                $error_messages[] = "Les tables n'existent pas";
            }

            $output_messages[] = "Deploiement Taches termine!";

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }

    public function deployPlanTravail()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "Debut du deploiement du module Plan de Travail...";

            if (!Schema::hasTable('activite_plans')) {
                $output_messages[] = "Creation de la table activite_plans...";

                try {
                    Schema::create('activite_plans', function ($table) {
                        $table->id();
                        $table->foreignId('createur_id')->constrained('agents')->onDelete('cascade');
                        $table->string('titre');
                        $table->text('description')->nullable();
                        $table->enum('niveau_administratif', ['SEN', 'SEP', 'SEL']);
                        $table->foreignId('departement_id')->nullable()->constrained('departments')->onDelete('set null');
                        $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
                        $table->foreignId('localite_id')->nullable()->constrained('localites')->onDelete('set null');
                        $table->unsignedSmallInteger('annee');
                        $table->enum('trimestre', ['T1', 'T2', 'T3', 'T4'])->nullable();
                        $table->enum('statut', ['planifiee', 'en_cours', 'terminee'])->default('planifiee');
                        $table->date('date_debut')->nullable();
                        $table->date('date_fin')->nullable();
                        $table->unsignedTinyInteger('pourcentage')->default(0);
                        $table->text('observations')->nullable();
                        $table->timestamps();
                        $table->index('niveau_administratif');
                        $table->index('annee');
                        $table->index('statut');
                        $table->index('departement_id');
                        $table->index('province_id');
                    });
                    $output_messages[] = "Table activite_plans creee!";
                } catch (\Exception $e) {
                    $error_messages[] = "Erreur table activite_plans: " . $e->getMessage();
                    return redirect()->route('admin.deployment.index')
                        ->with('error_messages', $error_messages)
                        ->with('output_messages', $output_messages);
                }
            } else {
                $output_messages[] = "Table activite_plans existe deja";
            }

            if (Schema::hasTable('activite_plans')) {
                $count = DB::table('activite_plans')->count();
                $output_messages[] = "Table activite_plans existe avec $count enregistrements";
                $success = true;
            } else {
                $error_messages[] = "La table activite_plans n'existe pas";
            }

            $output_messages[] = "Deploiement Plan de Travail termine!";

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return redirect()->route('admin.deployment.index')
            ->with('output_messages', $output_messages)
            ->with('error_messages', $error_messages)
            ->with('success', $success);
    }
}
