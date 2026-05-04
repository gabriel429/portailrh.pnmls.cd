<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            if (!Schema::hasColumn('taches', 'niveau_gestion')) {
                $table->enum('niveau_gestion', ['sen', 'departement', 'province', 'local'])
                    ->default('departement')
                    ->after('source_emetteur');
            }

            if (!Schema::hasColumn('taches', 'validation_responsable_role')) {
                $table->enum('validation_responsable_role', ['sen', 'directeur', 'sep', 'sel'])
                    ->nullable()
                    ->after('niveau_gestion');
            }

            if (!Schema::hasColumn('taches', 'validation_statut')) {
                $table->enum('validation_statut', ['non_requise', 'a_valider', 'validee', 'rejetee'])
                    ->default('non_requise')
                    ->after('pourcentage');
            }

            if (!Schema::hasColumn('taches', 'validation_commentaire')) {
                $table->text('validation_commentaire')->nullable()->after('validation_statut');
            }

            if (!Schema::hasColumn('taches', 'soumise_validation_at')) {
                $table->timestamp('soumise_validation_at')->nullable()->after('validation_commentaire');
            }

            if (!Schema::hasColumn('taches', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('soumise_validation_at')->constrained('agents')->nullOnDelete();
            }

            if (!Schema::hasColumn('taches', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            if (!Schema::hasColumn('taches', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('validated_at')->constrained('agents')->nullOnDelete();
            }

            if (!Schema::hasColumn('taches', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (!Schema::hasColumn('taches', 'blocked_by')) {
                $table->foreignId('blocked_by')->nullable()->after('rejected_at')->constrained('agents')->nullOnDelete();
            }

            if (!Schema::hasColumn('taches', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('blocked_by');
            }

            if (!Schema::hasColumn('taches', 'blocking_reason')) {
                $table->text('blocking_reason')->nullable()->after('blocked_at');
            }
        });

        DB::statement("ALTER TABLE taches MODIFY priorite ENUM('faible','normale','haute','urgente') DEFAULT 'normale'");
        DB::statement("ALTER TABLE taches MODIFY statut ENUM('nouvelle','en_cours','terminee','bloquee') DEFAULT 'nouvelle'");

        if (Schema::hasTable('tache_commentaires')) {
            Schema::table('tache_commentaires', function (Blueprint $table) {
                if (!Schema::hasColumn('tache_commentaires', 'type_commentaire')) {
                    $table->enum('type_commentaire', ['commentaire', 'relance', 'correction', 'blocage', 'validation', 'rejet', 'systeme'])
                        ->default('commentaire')
                        ->after('contenu');
                }
            });
        }

        if (!Schema::hasTable('tache_histories')) {
            Schema::create('tache_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tache_id')->constrained('taches')->onDelete('cascade');
                $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
                $table->string('action', 60);
                $table->string('action_label', 160)->nullable();
                $table->string('ancien_statut', 60)->nullable();
                $table->string('nouveau_statut', 60)->nullable();
                $table->string('ancien_validation_statut', 60)->nullable();
                $table->string('nouveau_validation_statut', 60)->nullable();
                $table->text('commentaire')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['tache_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tache_histories')) {
            Schema::dropIfExists('tache_histories');
        }

        if (Schema::hasTable('tache_commentaires') && Schema::hasColumn('tache_commentaires', 'type_commentaire')) {
            Schema::table('tache_commentaires', function (Blueprint $table) {
                $table->dropColumn('type_commentaire');
            });
        }

        DB::statement("ALTER TABLE taches MODIFY priorite ENUM('normale','haute','urgente') DEFAULT 'normale'");
        DB::statement("ALTER TABLE taches MODIFY statut ENUM('nouvelle','en_cours','terminee') DEFAULT 'nouvelle'");

        Schema::table('taches', function (Blueprint $table) {
            $columns = [
                'niveau_gestion',
                'validation_responsable_role',
                'validation_statut',
                'validation_commentaire',
                'soumise_validation_at',
                'validated_by',
                'validated_at',
                'rejected_by',
                'rejected_at',
                'blocked_by',
                'blocked_at',
                'blocking_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('taches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
