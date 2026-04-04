<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\InstitutionCategorie;
use App\Models\Institution;

class DeploymentController extends Controller
{
    private function deployResponse(array $output_messages, array $error_messages, bool $success)
    {
        $message = implode("\n", array_merge($output_messages, $error_messages));
        return response()->json(['success' => $success, 'message' => $message], $success ? 200 : 422);
    }

    private function runShellCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'output' => trim(implode("\n", $output)),
            'exit_code' => $exitCode,
        ];
    }

    private function resolveExecutable(string $command, array $fallbacks = []): ?string
    {
        $resolved = trim((string) shell_exec("command -v {$command} 2>/dev/null"));
        if ($resolved !== '') {
            return $resolved;
        }

        foreach ($fallbacks as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Exécute la commande php artisan migrate (applique les migrations sans perte de données)
     */
    public function migrate()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Lancement des migrations en attente...";
            $output_messages[] = "🧹 Purge des caches Laravel avant execution...";
            Artisan::call('optimize:clear');
            $clearOutput = trim(Artisan::output());
            if ($clearOutput !== '') {
                $output_messages[] = $clearOutput;
            }

            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = trim(Artisan::output());
            if ($migrateOutput !== '') {
                $output_messages[] = $migrateOutput;
            }

            $output_messages[] = "🧼 Purge finale des caches pour prendre en compte les changements...";
            Artisan::call('optimize:clear');
            $finalClearOutput = trim(Artisan::output());
            if ($finalClearOutput !== '') {
                $output_messages[] = $finalClearOutput;
            }

            $output_messages[] = "✅ Migrations et rafraichissement des caches termines !";
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Exécute migrate:fresh (supprime toutes les tables et recrée la base)
     */
    public function migrateFresh()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "⚠️ Réinitialisation complète de la base de données...";
            Artisan::call('migrate:fresh', ['--force' => true]);
            $output = Artisan::output();
            $output_messages[] = $output;
            $output_messages[] = "✅ Base de données réinitialisée avec succès !";
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
                }
            } else {
                $error_messages[] = "❌ La table users n'existe pas";
                return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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
                        return $this->deployResponse($output_messages, $error_messages, false);
                    }
                } else {
                    $output_messages[] = "✅ Colonne institution_id existe déjà";
                }
            } else {
                $error_messages[] = "❌ La table agents n'existe pas";
                return $this->deployResponse($output_messages, $error_messages, false);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
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
                    return $this->deployResponse($output_messages, $error_messages, false);
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

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Rename roles in database
     */
    public function deployRenameRoles()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "Debut du renommage des roles...";

            // Rename Chef Section RH -> Section ressources humaines
            $updated1 = DB::table('roles')
                ->where('nom_role', 'Chef Section RH')
                ->update([
                    'nom_role' => 'Section ressources humaines',
                    'description' => 'Section ressources humaines',
                ]);

            if ($updated1 > 0) {
                $output_messages[] = "Role 'Chef Section RH' renomme en 'Section ressources humaines'";
            } else {
                $exists1 = DB::table('roles')->where('nom_role', 'Section ressources humaines')->exists();
                if ($exists1) {
                    $output_messages[] = "Role 'Section ressources humaines' existe deja";
                } else {
                    $output_messages[] = "Role 'Chef Section RH' non trouve";
                }
            }

            // Rename Chef Section Nouvelle Technologie -> Section Nouvelle Technologie
            $updated2 = DB::table('roles')
                ->where('nom_role', 'Chef Section Nouvelle Technologie')
                ->update([
                    'nom_role' => 'Section Nouvelle Technologie',
                    'description' => 'Section Nouvelle Technologie',
                ]);

            // Also rename variant
            $updated3 = DB::table('roles')
                ->where('nom_role', 'Chef de Section Nouvelle Technologie')
                ->update([
                    'nom_role' => 'Section Nouvelle Technologie',
                    'description' => 'Section Nouvelle Technologie',
                ]);

            if ($updated2 > 0 || $updated3 > 0) {
                $output_messages[] = "Role 'Chef Section Nouvelle Technologie' renomme en 'Section Nouvelle Technologie'";
            } else {
                $exists2 = DB::table('roles')->where('nom_role', 'Section Nouvelle Technologie')->exists();
                if ($exists2) {
                    $output_messages[] = "Role 'Section Nouvelle Technologie' existe deja";
                } else {
                    $output_messages[] = "Role 'Chef Section Nouvelle Technologie' non trouve";
                }
            }

            // Verification
            $roles = DB::table('roles')->pluck('nom_role')->toArray();
            $output_messages[] = "Roles actuels: " . implode(', ', $roles);
            $success = true;

            $output_messages[] = "Renommage des roles termine!";

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Add domaine_etudes column to agents table
     */
    public function deployDomaineEtudes()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            if (Schema::hasColumn('agents', 'domaine_etudes')) {
                $output_messages[] = "La colonne 'domaine_etudes' existe deja.";
            } else {
                Schema::table('agents', function ($table) {
                    $table->string('domaine_etudes')->nullable()->after('niveau_etudes');
                });
                $output_messages[] = "Colonne 'domaine_etudes' ajoutee avec succes!";
            }

            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Seed departments table with PNMLS departments
     */
    public function deployDepartments()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $departments = [
                ['code' => 'DAF', 'nom' => 'Département Administration et Finances'],
                ['code' => 'DPP', 'nom' => 'Département Planification et Programmation'],
                ['code' => 'DSE', 'nom' => 'Département Suivi et Évaluation'],
                ['code' => 'DPC', 'nom' => 'Département Prévention et Communication'],
                ['code' => 'DPM', 'nom' => 'Département Prise en charge Médicale'],
                ['code' => 'DRH', 'nom' => 'Département Ressources Humaines'],
                ['code' => 'DPR', 'nom' => 'Département Passation des Marchés'],
                ['code' => 'DIR', 'nom' => 'Direction'],
                ['code' => 'SJU', 'nom' => 'Section Juridique'],
                ['code' => 'SCO', 'nom' => 'Section Communication'],
                ['code' => 'SNT', 'nom' => 'Section Nouvelle Technologie'],
                ['code' => 'SRH', 'nom' => 'Section Ressources Humaines'],
                ['code' => 'AUD', 'nom' => 'Audit Interne'],
            ];

            $created = 0;
            $existing = 0;

            foreach ($departments as $dept) {
                $exists = DB::table('departments')->where('code', $dept['code'])->exists();
                if (!$exists) {
                    DB::table('departments')->insert([
                        'code' => $dept['code'],
                        'nom' => $dept['nom'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                    $output_messages[] = "Créé: {$dept['nom']} ({$dept['code']})";
                } else {
                    $existing++;
                }
            }

            if ($existing > 0) {
                $output_messages[] = "{$existing} département(s) existaient déjà.";
            }
            $output_messages[] = "{$created} département(s) créé(s). Total: " . DB::table('departments')->count();
            $success = true;

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Déployer les grades de la Fonction Publique.
     */
    public function deployGrades()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            // Créer la table si elle n'existe pas
            if (!Schema::hasTable('grades')) {
                $output_messages[] = "Création de la table grades...";
                Schema::create('grades', function ($table) {
                    $table->id();
                    $table->char('categorie', 1);
                    $table->string('nom_categorie');
                    $table->unsignedTinyInteger('ordre');
                    $table->string('libelle');
                    $table->timestamps();
                });
                $output_messages[] = "Table grades créée.";
            } else {
                $output_messages[] = "Table grades existe déjà.";
            }

            $grades = [
                ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 1,  'libelle' => 'Secrétaire général'],
                ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 2,  'libelle' => 'Directeur'],
                ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 3,  'libelle' => 'Chef de Division'],
                ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 4,  'libelle' => 'Chef de Bureau'],
                ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 5,  'libelle' => "Attaché d'Administration de 1ère Classe"],
                ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 6,  'libelle' => "Attaché d'Administration de 2ème Classe"],
                ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 7,  'libelle' => "Agent d'Administration de 1ère Classe"],
                ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 8,  'libelle' => "Agent d'Administration de 2ème Classe"],
                ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 9,  'libelle' => "Agent Auxiliaire de 1ère Classe"],
                ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 10, 'libelle' => "Agent Auxiliaire de 2ème Classe"],
                ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 11, 'libelle' => 'Huissier'],
            ];

            $created = 0;
            $existing = 0;

            foreach ($grades as $grade) {
                $exists = DB::table('grades')->where('ordre', $grade['ordre'])->exists();
                if (!$exists) {
                    DB::table('grades')->insert(array_merge($grade, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $created++;
                    $output_messages[] = "Créé: {$grade['libelle']} ({$grade['categorie']})";
                } else {
                    $existing++;
                }
            }

            if ($existing > 0) {
                $output_messages[] = "{$existing} grade(s) existaient déjà.";
            }
            $output_messages[] = "{$created} grade(s) créé(s). Total: " . DB::table('grades')->count();
            $success = true;

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Déployer les tables sections, cellules, localites et affectations.
     */
    public function deployAffectations()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            // 1. Table sections
            if (!Schema::hasTable('sections')) {
                $output_messages[] = "Création de la table sections...";
                Schema::create('sections', function ($table) {
                    $table->id();
                    $table->string('code')->unique();
                    $table->string('nom');
                    $table->text('description')->nullable();
                    $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
                    $table->enum('type', ['section', 'service_rattache'])
                          ->default('section');
                    $table->timestamps();
                });
                $output_messages[] = "Table sections créée.";
            } else {
                $output_messages[] = "Table sections existe déjà.";
            }

            // 2. Table cellules
            if (!Schema::hasTable('cellules')) {
                $output_messages[] = "Création de la table cellules...";
                Schema::create('cellules', function ($table) {
                    $table->id();
                    $table->string('code')->unique();
                    $table->string('nom');
                    $table->text('description')->nullable();
                    $table->foreignId('section_id')->constrained()->onDelete('cascade');
                    $table->timestamps();
                });
                $output_messages[] = "Table cellules créée.";
            } else {
                $output_messages[] = "Table cellules existe déjà.";
            }

            // 3. Table localites
            if (!Schema::hasTable('localites')) {
                $output_messages[] = "Création de la table localites...";
                Schema::create('localites', function ($table) {
                    $table->id();
                    $table->string('code')->unique();
                    $table->string('nom');
                    $table->enum('type', ['territoire', 'zone_de_sante', 'commune', 'ville', 'autre'])->default('territoire');
                    $table->text('description')->nullable();
                    $table->foreignId('province_id')->constrained()->onDelete('cascade');
                    $table->timestamps();
                });
                $output_messages[] = "Table localites créée.";
            } else {
                $output_messages[] = "Table localites existe déjà.";
            }

            // 4. Table affectations
            if (!Schema::hasTable('affectations')) {
                $output_messages[] = "Création de la table affectations...";
                Schema::create('affectations', function ($table) {
                    $table->id();
                    $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                    $table->foreignId('fonction_id')->constrained('fonctions')->onDelete('restrict');
                    $table->enum('niveau_administratif', ['SEN', 'SEP', 'SEL'])->default('SEN');
                    $table->enum('niveau', [
                        'direction', 'service_rattache', 'département',
                        'section', 'cellule', 'province', 'local',
                    ])->default('département');
                    $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
                    $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
                    $table->foreignId('cellule_id')->nullable()->constrained('cellules')->onDelete('cascade');
                    $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
                    $table->foreignId('localite_id')->nullable()->constrained('localites')->onDelete('cascade');
                    $table->date('date_debut')->nullable();
                    $table->date('date_fin')->nullable();
                    $table->boolean('actif')->default(true);
                    $table->text('remarque')->nullable();
                    $table->timestamps();
                });
                $output_messages[] = "Table affectations créée.";
            } else {
                $output_messages[] = "Table affectations existe déjà.";
            }

            $output_messages[] = "Déploiement terminé avec succès!";
            $success = true;

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Pull latest code from GitHub, clear caches, and run migrations.
     */
    public function gitPull()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $root = base_path();
            $php = PHP_BINARY;

            // Use Hostinger PHP path if available
            if (file_exists('/opt/alt/php83/usr/bin/php')) {
                $php = '/opt/alt/php83/usr/bin/php';
            }

            $output_messages[] = "=== Git Pull origin main ===";
            $gitOutput = shell_exec("cd {$root} && git pull origin main 2>&1");
            $output_messages[] = $gitOutput ?: '(aucune sortie)';

            $output_messages[] = "=== Nettoyage des caches ===";
            Artisan::call('config:clear');
            $output_messages[] = Artisan::output();
            Artisan::call('route:clear');
            $output_messages[] = Artisan::output();
            Artisan::call('view:clear');
            $output_messages[] = Artisan::output();

            $output_messages[] = "=== Migration ===";
            Artisan::call('migrate', ['--force' => true]);
            $output_messages[] = Artisan::output();

            $output_messages[] = "Déploiement Git terminé avec succès!";
            $success = true;

        } catch (\Exception $e) {
            $error_messages[] = "ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Rebuild frontend assets with Vite and clear Laravel caches.
     */
    public function buildFrontend()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $root = base_path();

            $nodePath = $this->resolveExecutable('node', [
                '/opt/alt/alt-nodejs24/root/usr/bin/node',
                '/opt/alt/alt-nodejs22/root/usr/bin/node',
                '/opt/alt/alt-nodejs20/root/usr/bin/node',
                '/opt/alt/alt-nodejs18/root/usr/bin/node',
            ]);
            $npmPath = $this->resolveExecutable('npm', [
                '/opt/alt/alt-nodejs24/root/usr/bin/npm',
                '/opt/alt/alt-nodejs22/root/usr/bin/npm',
                '/opt/alt/alt-nodejs20/root/usr/bin/npm',
                '/opt/alt/alt-nodejs18/root/usr/bin/npm',
            ]);

            if ($npmPath === null || $nodePath === null) {
                throw new \RuntimeException('Node.js ou npm est introuvable sur le serveur.');
            }

            $quotedRoot = escapeshellarg($root);
            $quotedNpm = escapeshellarg($npmPath);
            $quotedNode = escapeshellarg($nodePath);
            $binDir = escapeshellarg(dirname($npmPath));
            $shellPrefix = "export PATH={$binDir}:$PATH;";

            $output_messages[] = '=== Verification de l environnement Node ===';

            $nodeVersion = $this->runShellCommand("{$quotedNode} -v");
            $npmVersion = $this->runShellCommand("{$quotedNpm} -v");
            $output_messages[] = $nodeVersion['output'] ?: '(version node indisponible)';
            $output_messages[] = $npmVersion['output'] ?: '(version npm indisponible)';

            $output_messages[] = '=== Installation des dependances frontend ===';
            $install = $this->runShellCommand("{$shellPrefix} cd {$quotedRoot} && {$quotedNpm} install --legacy-peer-deps");
            $output_messages[] = $install['output'] ?: '(aucune sortie npm install)';
            if ($install['exit_code'] !== 0) {
                throw new \RuntimeException('npm install a echoue.');
            }

            $output_messages[] = '=== Build Vite ===';
            $build = $this->runShellCommand("{$shellPrefix} cd {$quotedRoot} && {$quotedNpm} run build");
            $output_messages[] = $build['output'] ?: '(aucune sortie npm run build)';
            if ($build['exit_code'] !== 0) {
                throw new \RuntimeException('npm run build a echoue.');
            }

            $output_messages[] = '=== Nettoyage final des caches Laravel ===';
            Artisan::call('optimize:clear');
            $clearOutput = trim(Artisan::output());
            if ($clearOutput !== '') {
                $output_messages[] = $clearOutput;
            }

            $output_messages[] = '✅ Build frontend termine avec succes !';
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = '❌ ERREUR: ' . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Import des 65 agents SEN depuis le seeder AgentImportSeeder
     */
    public function deployAgents()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Lancement de l'import des agents SEN...";

            $countBefore = DB::table('agents')->count();

            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\AgentImportSeeder',
                '--force' => true,
            ]);
            $output = Artisan::output();
            $output_messages[] = $output;

            $countAfter = DB::table('agents')->count();
            $imported = $countAfter - $countBefore;

            $output_messages[] = "📊 Agents avant: {$countBefore}, après: {$countAfter} (+{$imported} nouveaux)";
            $output_messages[] = "✅ Import des agents terminé !";
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Exécuter le seeder SuperAdmin
     */
    public function seedSuperAdmin()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            $output_messages[] = "🚀 Lancement du seeder SuperAdmin...";

            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\SuperAdminSeeder',
                '--force' => true,
            ]);
            $output = Artisan::output();
            $output_messages[] = $output;

            $output_messages[] = "✅ Seeder SuperAdmin exécuté avec succès !";
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }

    /**
     * Deploy Holiday Tables (holiday_plannings, agent_statuses, holidays)
     */
    public function deployHolidays()
    {
        $output_messages = [];
        $error_messages = [];
        $success = false;

        try {
            // ── 1. Table holiday_plannings ──
            if (!Schema::hasTable('holiday_plannings')) {
                Schema::create('holiday_plannings', function (Blueprint $table) {
                    $table->id();
                    $table->year('annee');
                    $table->string('type_structure');
                    $table->unsignedBigInteger('structure_id');
                    $table->string('nom_structure');
                    $table->integer('jours_conge_totaux')->default(30);
                    $table->integer('jours_utilises')->default(0);
                    $table->json('periods_fermeture')->nullable();
                    $table->text('notes')->nullable();
                    $table->boolean('valide')->default(false);
                    $table->unsignedBigInteger('created_by');
                    $table->unsignedBigInteger('validated_by')->nullable();
                    $table->timestamp('validated_at')->nullable();
                    $table->timestamps();

                    $table->index(['annee', 'type_structure']);
                    $table->index(['structure_id', 'type_structure']);
                    $table->unique(['annee', 'type_structure', 'structure_id'], 'unique_planning_structure');

                    $table->foreign('created_by')->references('id')->on('agents')->onDelete('cascade');
                    $table->foreign('validated_by')->references('id')->on('agents')->onDelete('set null');
                });
                $output_messages[] = "✅ Table 'holiday_plannings' créée avec succès.";
            } else {
                $output_messages[] = "ℹ️ Table 'holiday_plannings' existe déjà.";
            }

            // ── 2. Table agent_statuses ──
            if (!Schema::hasTable('agent_statuses')) {
                Schema::create('agent_statuses', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('agent_id');
                    $table->enum('statut', ['disponible', 'en_conge', 'en_mission', 'suspendu', 'en_formation']);
                    $table->date('date_debut');
                    $table->date('date_fin')->nullable();
                    $table->string('motif')->nullable();
                    $table->text('commentaire')->nullable();
                    $table->string('document_joint')->nullable();
                    $table->boolean('actuel')->default(true);
                    $table->unsignedBigInteger('created_by');
                    $table->unsignedBigInteger('approved_by')->nullable();
                    $table->timestamp('approved_at')->nullable();
                    $table->timestamps();

                    $table->index(['agent_id', 'actuel']);
                    $table->index(['agent_id', 'date_debut', 'date_fin']);
                    $table->index('statut');

                    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
                    $table->foreign('created_by')->references('id')->on('agents')->onDelete('cascade');
                    $table->foreign('approved_by')->references('id')->on('agents')->onDelete('set null');
                });
                $output_messages[] = "✅ Table 'agent_statuses' créée avec succès.";
            } else {
                $output_messages[] = "ℹ️ Table 'agent_statuses' existe déjà.";
            }

            // ── 3. Table holidays ──
            if (!Schema::hasTable('holidays')) {
                Schema::create('holidays', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('agent_id');
                    $table->unsignedBigInteger('holiday_planning_id')->nullable();
                    $table->date('date_debut');
                    $table->date('date_fin');
                    $table->integer('nombre_jours');
                    $table->enum('type_conge', ['annuel', 'maladie', 'maternite', 'paternite', 'urgence', 'special']);
                    $table->enum('statut_demande', ['en_attente', 'approuve', 'refuse', 'annule']);
                    $table->text('motif')->nullable();
                    $table->text('commentaire_refus')->nullable();
                    $table->string('document_medical')->nullable();
                    $table->boolean('report_possible')->default(false);
                    $table->date('date_retour_prevu');
                    $table->date('date_retour_effectif')->nullable();
                    $table->unsignedBigInteger('demande_par');
                    $table->unsignedBigInteger('approuve_par')->nullable();
                    $table->timestamp('approuve_le')->nullable();
                    $table->unsignedBigInteger('refuse_par')->nullable();
                    $table->timestamp('refuse_le')->nullable();
                    $table->timestamps();

                    $table->index(['agent_id', 'date_debut', 'date_fin']);
                    $table->index(['date_debut', 'date_fin']);
                    $table->index(['statut_demande', 'type_conge']);
                    $table->index('holiday_planning_id');

                    $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
                    $table->foreign('holiday_planning_id')->references('id')->on('holiday_plannings')->onDelete('set null');
                    $table->foreign('demande_par')->references('id')->on('agents')->onDelete('cascade');
                    $table->foreign('approuve_par')->references('id')->on('agents')->onDelete('set null');
                    $table->foreign('refuse_par')->references('id')->on('agents')->onDelete('set null');
                });
                $output_messages[] = "✅ Table 'holidays' créée avec succès.";
            } else {
                $output_messages[] = "ℹ️ Table 'holidays' existe déjà.";
            }

            $output_messages[] = "";
            $output_messages[] = "🎉 Déploiement du module Congés terminé !";
            $success = true;
        } catch (\Exception $e) {
            $error_messages[] = "❌ ERREUR: " . $e->getMessage();
        }

        return $this->deployResponse($output_messages, $error_messages, $success);
    }
}
