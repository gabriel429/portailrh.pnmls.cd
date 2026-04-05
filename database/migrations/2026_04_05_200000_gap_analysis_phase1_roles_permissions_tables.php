<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. NOUVEAUX RÔLES
        // ──────────────────────────────────────────────
        $newRoles = [
            ['nom_role' => 'SENA', 'description' => 'Secrétaire Exécutif National Adjoint'],
            ['nom_role' => 'Chef Section Renforcement', 'description' => 'Chef de la Section Renforcement des Capacités (DRRC)'],
            ['nom_role' => 'Chef Cellule Renforcement', 'description' => 'Chef de la Cellule Renforcement des Capacités'],
            ['nom_role' => 'Chef Section Planification', 'description' => 'Chef de la Section Planification (PTA)'],
            ['nom_role' => 'Cellule Planification', 'description' => 'Cellule Planification / PSE'],
            ['nom_role' => 'Chef Section Juridique', 'description' => 'Chef de la Section Juridique (Signalements)'],
        ];

        foreach ($newRoles as $role) {
            DB::table('roles')->insertOrIgnore([
                'nom_role'    => $role['nom_role'],
                'description' => $role['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ──────────────────────────────────────────────
        // 2. NOUVELLES PERMISSIONS
        // ──────────────────────────────────────────────
        $newPermissions = [
            // Demandes – workflow multi-étapes
            ['nom' => 'Voir demandes département', 'code' => 'demande.view_departement', 'description' => 'Voir les demandes de son département'],
            ['nom' => 'Valider demande (Directeur)', 'code' => 'demande.validate_director', 'description' => 'Valider une demande au niveau Directeur'],
            ['nom' => 'Réviser demande (RH)', 'code' => 'demande.review_rh', 'description' => 'Réviser et traiter une demande au niveau RH'],
            ['nom' => 'Valider demande (SEP)', 'code' => 'demande.validate_sep', 'description' => 'Valider une demande au niveau SEP'],
            ['nom' => 'Valider demande (SEN)', 'code' => 'demande.validate_sen', 'description' => 'Valider une demande au niveau SEN'],

            // Renforcement des capacités
            ['nom' => 'Voir renforcement', 'code' => 'renforcement.view', 'description' => 'Voir les formations et renforcements'],
            ['nom' => 'Traiter renforcement', 'code' => 'renforcement.process', 'description' => 'Traiter les demandes de renforcement'],
            ['nom' => 'Planifier formation', 'code' => 'renforcement.plan', 'description' => 'Planifier une formation'],
            ['nom' => 'Valider formation', 'code' => 'renforcement.validate', 'description' => 'Valider une formation planifiée'],
            ['nom' => 'Suivre formation', 'code' => 'renforcement.monitor', 'description' => 'Suivre l\'exécution des formations'],
            ['nom' => 'Rapport mensuel renforcement', 'code' => 'renforcement.report.monthly', 'description' => 'Générer rapport mensuel renforcement'],
            ['nom' => 'Rapport annuel renforcement', 'code' => 'renforcement.report.annual', 'description' => 'Générer rapport annuel renforcement'],

            // PTA
            ['nom' => 'Créer activité PTA', 'code' => 'pta.create', 'description' => 'Créer une activité du plan de travail'],
            ['nom' => 'Modifier activité PTA', 'code' => 'pta.update', 'description' => 'Modifier une activité PTA'],
            ['nom' => 'Réviser PTA', 'code' => 'pta.review', 'description' => 'Réviser les activités PTA'],
            ['nom' => 'Valider PTA (Section)', 'code' => 'pta.validate_section', 'description' => 'Valider le PTA au niveau section'],
            ['nom' => 'Valider PTA (Cellule)', 'code' => 'pta.validate_cellule', 'description' => 'Valider le PTA au niveau cellule'],
            ['nom' => 'Suivre PTA', 'code' => 'pta.monitor', 'description' => 'Suivre l\'avancement du PTA'],
            ['nom' => 'Mettre à jour progression PTA', 'code' => 'pta.update_progress', 'description' => 'Mettre à jour le pourcentage d\'avancement'],
            ['nom' => 'Tableau de bord PTA', 'code' => 'pta.dashboard', 'description' => 'Accéder au tableau de bord PTA'],
            ['nom' => 'Exporter PTA Excel', 'code' => 'pta.export_excel', 'description' => 'Exporter le PTA en Excel'],

            // Signalements
            ['nom' => 'Voir tous les signalements', 'code' => 'signalement.view_all', 'description' => 'Voir tous les signalements'],
            ['nom' => 'Analyser signalement', 'code' => 'signalement.analyze', 'description' => 'Analyser un signalement'],
            ['nom' => 'Traiter signalement', 'code' => 'signalement.process', 'description' => 'Traiter un signalement'],
            ['nom' => 'Fermer signalement', 'code' => 'signalement.close', 'description' => 'Fermer un signalement'],
            ['nom' => 'Rapport mensuel signalements', 'code' => 'signalement.report.monthly', 'description' => 'Rapport mensuel des signalements'],
            ['nom' => 'Rapport annuel signalements', 'code' => 'signalement.report.annual', 'description' => 'Rapport annuel des signalements'],

            // Congés
            ['nom' => 'Voir ses congés', 'code' => 'conge.view_own', 'description' => 'Voir ses propres congés'],
            ['nom' => 'Voir congés équipe', 'code' => 'conge.view_team', 'description' => 'Voir les congés de son équipe'],
            ['nom' => 'Demander congé', 'code' => 'conge.request', 'description' => 'Soumettre une demande de congé'],
            ['nom' => 'Modifier son congé', 'code' => 'conge.update_own', 'description' => 'Modifier sa propre demande de congé'],
            ['nom' => 'Planifier congés national', 'code' => 'conge.plan_national', 'description' => 'Gérer le planning national des congés'],
            ['nom' => 'Planifier congés provincial', 'code' => 'conge.plan_provincial', 'description' => 'Gérer le planning provincial des congés'],
            ['nom' => 'Valider congé national', 'code' => 'conge.validate_national', 'description' => 'Valider un congé au niveau national'],
            ['nom' => 'Valider congé provincial', 'code' => 'conge.validate_provincial', 'description' => 'Valider un congé au niveau provincial'],
            ['nom' => 'Voir tous les congés', 'code' => 'conge.view_all', 'description' => 'Voir tous les congés'],
            ['nom' => 'Voir congés province', 'code' => 'conge.view_province', 'description' => 'Voir les congés de sa province'],
            ['nom' => 'Exporter congés', 'code' => 'conge.export', 'description' => 'Exporter les données congés'],

            // Tâches
            ['nom' => 'Créer tâche', 'code' => 'task.create', 'description' => 'Créer une tâche'],
            ['nom' => 'Lier tâche au PTA', 'code' => 'task.link_pta', 'description' => 'Lier une tâche à une activité PTA'],
            ['nom' => 'Voir ses tâches', 'code' => 'task.view_own', 'description' => 'Voir ses tâches assignées'],
            ['nom' => 'Mettre à jour statut tâche', 'code' => 'task.update_status', 'description' => 'Mettre à jour le statut d\'une tâche'],
            ['nom' => 'Soumettre rapport tâche', 'code' => 'task.submit_report', 'description' => 'Soumettre un rapport d\'exécution'],
            ['nom' => 'Voir rapports tâches', 'code' => 'task.report.view', 'description' => 'Voir les rapports d\'exécution'],
            ['nom' => 'Rapport performance', 'code' => 'task.report.performance', 'description' => 'Rapport de performance par agent'],

            // Communication
            ['nom' => 'Envoyer messages RH', 'code' => 'message.send_rh', 'description' => 'Envoyer un message au RH'],
            ['nom' => 'Répondre message', 'code' => 'message.reply', 'description' => 'Répondre à un message'],
            ['nom' => 'Message ciblé', 'code' => 'message.send_targeted', 'description' => 'Envoyer un message ciblé'],
            ['nom' => 'Diffuser communiqué', 'code' => 'announcement.broadcast', 'description' => 'Diffuser un communiqué'],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'nom'         => $perm['nom'],
                'code'        => $perm['code'],
                'description' => $perm['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ──────────────────────────────────────────────
        // 3. MATRICE RÔLE ↔ PERMISSION
        // ──────────────────────────────────────────────
        $matrix = [
            'Agent' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'task.view_own', 'task.update_status', 'task.submit_report',
                'message.send_rh', 'message.reply',
            ],
            'Directeur' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'demande.view_departement', 'demande.validate_director',
                'view_agents', 'view_agent_detail',
                'pta.create', 'pta.update', 'pta.update_progress', 'pta.export_excel',
                'conge.view_own', 'conge.request', 'conge.update_own', 'conge.view_team',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.submit_report', 'task.report.view',
                'message.send_rh', 'message.reply',
            ],
            'RH Provincial' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'view_agents', 'view_agent_detail', 'create_agent', 'edit_agent',
                'view_requests', 'demande.review_rh',
                'view_documents', 'create_document', 'edit_document',
                'view_pointages', 'create_pointage', 'edit_pointage',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'conge.view_province', 'conge.plan_provincial', 'conge.validate_provincial',
                'task.create', 'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply', 'message.send_targeted',
            ],
            'RH National' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'view_agents', 'view_agent_detail', 'create_agent', 'edit_agent', 'delete_agent',
                'view_requests', 'approve_request', 'reject_request', 'demande.review_rh',
                'view_documents', 'create_document', 'edit_document', 'validate_document', 'delete_document',
                'view_pointages', 'create_pointage', 'edit_pointage',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'conge.view_all', 'conge.plan_national', 'conge.validate_national', 'conge.export',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.report.view', 'task.report.performance',
                'message.send_rh', 'message.reply', 'message.send_targeted',
                'announcement.broadcast',
            ],
            'Section ressources humaines' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement', 'access_admin',
                'view_agents', 'view_agent_detail', 'create_agent', 'edit_agent', 'delete_agent',
                'view_requests', 'approve_request', 'reject_request', 'demande.review_rh',
                'view_documents', 'create_document', 'edit_document', 'validate_document', 'delete_document',
                'view_pointages', 'create_pointage', 'edit_pointage',
                'view_signalements', 'edit_signalement',
                'view_roles', 'create_role', 'edit_role', 'view_permissions',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'conge.view_all', 'conge.plan_national', 'conge.validate_national', 'conge.export',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.report.view', 'task.report.performance',
                'message.send_rh', 'message.reply', 'message.send_targeted',
                'announcement.broadcast',
            ],
            'Section Nouvelle Technologie' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement', 'access_admin',
                'view_agents', 'view_agent_detail',
                'view_roles', 'create_role', 'edit_role', 'view_permissions',
                'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply',
            ],
            'SEP' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'view_agents', 'view_agent_detail',
                'view_requests', 'demande.validate_sep',
                'pta.create', 'pta.update', 'pta.review', 'pta.monitor',
                'pta.update_progress', 'pta.dashboard', 'pta.export_excel',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'conge.view_province', 'conge.plan_provincial', 'conge.validate_provincial',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.report.view',
                'message.send_rh', 'message.reply', 'message.send_targeted',
                'announcement.broadcast',
            ],
            'SEN' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement', 'access_admin',
                'view_agents', 'view_agent_detail', 'create_agent', 'edit_agent', 'delete_agent',
                'view_requests', 'approve_request', 'reject_request', 'demande.validate_sen',
                'view_documents', 'create_document', 'edit_document', 'validate_document', 'delete_document',
                'view_pointages',
                'view_signalements',
                'view_roles', 'view_permissions',
                'pta.create', 'pta.update', 'pta.review', 'pta.validate_section',
                'pta.validate_cellule', 'pta.monitor', 'pta.dashboard', 'pta.export_excel',
                'conge.view_own', 'conge.request', 'conge.update_own',
                'conge.view_all', 'conge.plan_national', 'conge.validate_national', 'conge.export',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.report.view', 'task.report.performance',
                'message.send_rh', 'message.reply', 'message.send_targeted',
                'announcement.broadcast',
                'renforcement.view', 'renforcement.monitor',
                'signalement.view_all',
                'signalement.report.monthly', 'signalement.report.annual',
                'renforcement.report.monthly', 'renforcement.report.annual',
            ],
            'SENA' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'view_agents', 'view_agent_detail',
                'view_requests', 'approve_request',
                'pta.review', 'pta.monitor', 'pta.dashboard',
                'conge.view_own', 'conge.request', 'conge.update_own', 'conge.view_all',
                'task.view_own', 'task.update_status', 'task.report.view',
                'message.send_rh', 'message.reply', 'message.send_targeted',
                'announcement.broadcast',
                'renforcement.view', 'renforcement.monitor',
            ],
            'Chef Section Renforcement' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'renforcement.view', 'renforcement.process', 'renforcement.plan',
                'renforcement.monitor', 'renforcement.report.monthly', 'renforcement.report.annual',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply',
            ],
            'Chef Cellule Renforcement' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'renforcement.view', 'renforcement.validate', 'renforcement.monitor',
                'renforcement.report.monthly', 'renforcement.report.annual',
                'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply',
            ],
            'Chef Section Planification' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'pta.review', 'pta.validate_section', 'pta.monitor', 'pta.dashboard', 'pta.export_excel',
                'task.create', 'task.link_pta', 'task.view_own', 'task.update_status',
                'task.report.view',
                'message.send_rh', 'message.reply',
            ],
            'Cellule Planification' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'pta.review', 'pta.validate_cellule', 'pta.monitor', 'pta.dashboard', 'pta.export_excel',
                'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply',
            ],
            'Chef Section Juridique' => [
                'view_own_profile', 'edit_own_profile', 'view_own_pointages', 'view_own_documents',
                'create_requests', 'create_signalement',
                'signalement.view_all', 'signalement.analyze', 'signalement.process', 'signalement.close',
                'signalement.report.monthly', 'signalement.report.annual',
                'view_signalements', 'edit_signalement',
                'task.view_own', 'task.update_status',
                'message.send_rh', 'message.reply',
            ],
        ];

        // Build lookup caches
        $roleIds = DB::table('roles')->pluck('id', 'nom_role');
        $permIds = DB::table('permissions')->pluck('id', 'code');

        $pivotRows = [];
        foreach ($matrix as $roleName => $permCodes) {
            $roleId = $roleIds[$roleName] ?? null;
            if (!$roleId) continue;

            foreach ($permCodes as $code) {
                $permId = $permIds[$code] ?? null;
                if (!$permId) continue;

                $pivotRows[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
        }

        // Insert in chunks, ignore duplicates
        foreach (array_chunk($pivotRows, 100) as $chunk) {
            DB::table('role_permission')->insertOrIgnore($chunk);
        }

        // ──────────────────────────────────────────────
        // 4. COLONNES WORKFLOW DEMANDES
        // ──────────────────────────────────────────────
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'current_step')) {
                $table->string('current_step', 30)->nullable()->default(null)->after('statut')
                      ->comment('Étape actuelle: directeur, rh, sep, sen');
            }
            if (!Schema::hasColumn('requests', 'validated_by_director')) {
                $table->unsignedBigInteger('validated_by_director')->nullable()->after('current_step');
                $table->timestamp('validated_at_director')->nullable();
            }
            if (!Schema::hasColumn('requests', 'validated_by_rh')) {
                $table->unsignedBigInteger('validated_by_rh')->nullable();
                $table->timestamp('validated_at_rh')->nullable();
            }
            if (!Schema::hasColumn('requests', 'validated_by_sep')) {
                $table->unsignedBigInteger('validated_by_sep')->nullable();
                $table->timestamp('validated_at_sep')->nullable();
            }
            if (!Schema::hasColumn('requests', 'validated_by_sen')) {
                $table->unsignedBigInteger('validated_by_sen')->nullable();
                $table->timestamp('validated_at_sen')->nullable();
            }
        });

        // ──────────────────────────────────────────────
        // 5. SIGNALEMENTS: agent_id NULLABLE (anonymat)
        // ──────────────────────────────────────────────
        if (Schema::hasColumn('signalements', 'agent_id')) {
            // Drop existing FK safely (name may vary)
            try {
                Schema::table('signalements', function (Blueprint $table) {
                    $table->dropForeign(['agent_id']);
                });
            } catch (\Exception $e) {
                // FK may not exist or have a different name – try raw SQL
                try {
                    $fkName = DB::selectOne("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'signalements'
                          AND COLUMN_NAME = 'agent_id'
                          AND REFERENCED_TABLE_NAME IS NOT NULL
                        LIMIT 1
                    ");
                    if ($fkName) {
                        DB::statement("ALTER TABLE signalements DROP FOREIGN KEY `{$fkName->CONSTRAINT_NAME}`");
                    }
                } catch (\Exception $e2) {
                    // No FK to drop – continue
                }
            }

            Schema::table('signalements', function (Blueprint $table) {
                $table->unsignedBigInteger('agent_id')->nullable()->change();
            });
            Schema::table('signalements', function (Blueprint $table) {
                $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');
            });
            // Add is_anonymous flag
            if (!Schema::hasColumn('signalements', 'is_anonymous')) {
                Schema::table('signalements', function (Blueprint $table) {
                    $table->boolean('is_anonymous')->default(false)->after('agent_id');
                });
            }
            // Add traite_par (who processed it)
            if (!Schema::hasColumn('signalements', 'traite_par')) {
                Schema::table('signalements', function (Blueprint $table) {
                    $table->unsignedBigInteger('traite_par')->nullable()->after('statut');
                    $table->timestamp('traite_le')->nullable()->after('traite_par');
                });
            }
        }

        // ──────────────────────────────────────────────
        // 6. TABLE: formations
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('formations')) {
            Schema::create('formations', function (Blueprint $table) {
                $table->id();
                $table->string('titre');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('request_id')->nullable();
                $table->text('objectif')->nullable();
                $table->string('lieu')->nullable();
                $table->string('formateur')->nullable();
                $table->date('date_debut');
                $table->date('date_fin');
                $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->decimal('budget', 18, 2)->nullable();
                $table->timestamps();

                $table->foreign('request_id')->references('id')->on('requests')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('agents')->onDelete('cascade');
                $table->foreign('validated_by')->references('id')->on('agents')->onDelete('set null');
            });
        }

        // ──────────────────────────────────────────────
        // 7. TABLE: formation_beneficiaires
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('formation_beneficiaires')) {
            Schema::create('formation_beneficiaires', function (Blueprint $table) {
                $table->id();
                $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->enum('statut', ['inscrit', 'confirme', 'present', 'absent', 'certifie'])->default('inscrit');
                $table->text('note_evaluation')->nullable();
                $table->string('certificat')->nullable();
                $table->timestamps();

                $table->unique(['formation_id', 'agent_id']);
            });
        }

        // ──────────────────────────────────────────────
        // 8. TABLE: task_reports
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('task_reports')) {
            Schema::create('task_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tache_id')->constrained('taches')->onDelete('cascade');
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->text('rapport');
                $table->string('fichier')->nullable();
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // 9. TABLE: conges_regles_departement
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('conges_regles_departement')) {
            Schema::create('conges_regles_departement', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->unique()->constrained('departments')->onDelete('cascade');
                $table->integer('max_consecutif')->default(30);
                $table->decimal('taux_absent_max', 5, 2)->default(20.00)
                      ->comment('Pourcentage max d\'absents simultanés');
                $table->integer('jours_annuels')->default(30);
                $table->timestamps();
            });
        }

        // ──────────────────────────────────────────────
        // 10. TABLE: conges_conflits
        // ──────────────────────────────────────────────
        if (!Schema::hasTable('conges_conflits')) {
            Schema::create('conges_conflits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('holiday_id')->constrained('holidays')->onDelete('cascade');
                $table->unsignedBigInteger('activite_plan_id')->nullable();
                $table->enum('type_conflit', ['chevauchement_conge', 'conflit_pta', 'taux_absent_depasse']);
                $table->text('description')->nullable();
                $table->boolean('resolue')->default(false);
                $table->unsignedBigInteger('resolue_par')->nullable();
                $table->timestamp('resolue_le')->nullable();
                $table->timestamps();

                $table->foreign('activite_plan_id')->references('id')->on('activite_plans')->onDelete('set null');
                $table->foreign('resolue_par')->references('id')->on('agents')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conges_conflits');
        Schema::dropIfExists('conges_regles_departement');
        Schema::dropIfExists('task_reports');
        Schema::dropIfExists('formation_beneficiaires');
        Schema::dropIfExists('formations');

        // Revert signalements
        if (Schema::hasColumn('signalements', 'traite_par')) {
            Schema::table('signalements', function (Blueprint $table) {
                $table->dropColumn(['traite_par', 'traite_le']);
            });
        }
        if (Schema::hasColumn('signalements', 'is_anonymous')) {
            Schema::table('signalements', function (Blueprint $table) {
                $table->dropColumn('is_anonymous');
            });
        }

        // Revert requests
        $cols = ['current_step', 'validated_by_director', 'validated_at_director',
                 'validated_by_rh', 'validated_at_rh', 'validated_by_sep', 'validated_at_sep',
                 'validated_by_sen', 'validated_at_sen'];
        Schema::table('requests', function (Blueprint $table) use ($cols) {
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('requests', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        // Revert role_permission (all new entries)
        // Not easily reversible without tracking - leave data in place

        // New roles (optional cleanup)
        $newRoleNames = ['SENA', 'Chef Section Renforcement', 'Chef Cellule Renforcement',
                         'Chef Section Planification', 'Cellule Planification', 'Chef Section Juridique'];
        DB::table('roles')->whereIn('nom_role', $newRoleNames)->delete();
    }
};
