<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            if (!Schema::hasColumn('taches', 'pourcentage')) {
                $table->unsignedTinyInteger('pourcentage')
                    ->default(0)
                    ->after('statut');
            }
        });

        if (Schema::hasColumn('taches', 'source_emetteur')) {
            DB::statement("ALTER TABLE taches MODIFY source_emetteur ENUM('directeur', 'assistant_departement', 'sen', 'sep', 'sel', 'autre') DEFAULT 'autre'");
        }

        if (!Schema::hasTable('tache_documents')) {
            Schema::create('tache_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tache_id')->constrained('taches')->onDelete('cascade');
                $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
                $table->foreignId('tache_commentaire_id')->nullable()->constrained('tache_commentaires')->nullOnDelete();
                $table->enum('type_document', ['initial', 'progression', 'final']);
                $table->string('titre');
                $table->string('fichier');
                $table->string('nom_original');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('taille')->default(0);
                $table->timestamps();

                $table->index(['tache_id', 'type_document']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tache_documents')) {
            Schema::dropIfExists('tache_documents');
        }

        if (Schema::hasColumn('taches', 'source_emetteur')) {
            DB::statement("ALTER TABLE taches MODIFY source_emetteur ENUM('directeur', 'assistant_departement', 'sen', 'autre') DEFAULT 'autre'");
        }

        Schema::table('taches', function (Blueprint $table) {
            if (Schema::hasColumn('taches', 'pourcentage')) {
                $table->dropColumn('pourcentage');
            }
        });
    }
};