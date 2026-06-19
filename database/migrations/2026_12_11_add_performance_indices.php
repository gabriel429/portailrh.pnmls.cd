<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index for agents table
        Schema::table('agents', function (Blueprint $table) {
            if (!$this->indexExists('agents', 'agents_matricule_index')) {
                $table->index('matricule');
            }
            if (!$this->indexExists('agents', 'agents_email_index')) {
                $table->index('email');
            }
            if (!$this->indexExists('agents', 'agents_statut_index')) {
                $table->index('statut');
            }
            if (!$this->indexExists('agents', 'agents_department_id_index')) {
                $table->index('department_id');
            }
            if (!$this->indexExists('agents', 'agents_province_id_index')) {
                $table->index('province_id');
            }
            if (!$this->indexExists('agents', 'agents_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('agents', 'agents_statut_department_id_index')) {
                $table->index(['statut', 'department_id']);
            }
        });

        // Index for holidays table
        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                if (!$this->indexExists('holidays', 'holidays_agent_id_index')) {
                    $table->index('agent_id');
                }
                if (!$this->indexExists('holidays', 'holidays_statut_index')) {
                    $table->index('statut');
                }
                if (!$this->indexExists('holidays', 'holidays_date_debut_date_fin_index')) {
                    $table->index(['date_debut', 'date_fin']);
                }
                if (!$this->indexExists('holidays', 'holidays_agent_id_statut_index')) {
                    $table->index(['agent_id', 'statut']);
                }
            });
        }

        // Index for pointages
        if (Schema::hasTable('pointages')) {
            Schema::table('pointages', function (Blueprint $table) {
                if (!$this->indexExists('pointages', 'pointages_agent_id_index')) {
                    $table->index('agent_id');
                }
                if (!$this->indexExists('pointages', 'pointages_date_index')) {
                    $table->index('date');
                }
                if (!$this->indexExists('pointages', 'pointages_agent_id_date_index')) {
                    $table->index(['agent_id', 'date']);
                }
            });
        }

        // Index for documents
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (!$this->indexExists('documents', 'documents_agent_id_index')) {
                    $table->index('agent_id');
                }
                if (!$this->indexExists('documents', 'documents_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        // Index for requests
        if (Schema::hasTable('requests')) {
            Schema::table('requests', function (Blueprint $table) {
                if (!$this->indexExists('requests', 'requests_agent_id_index')) {
                    $table->index('agent_id');
                }
                if (!$this->indexExists('requests', 'requests_type_index')) {
                    $table->index('type');
                }
                if (!$this->indexExists('requests', 'requests_statut_index')) {
                    $table->index('statut');
                }
                if (!$this->indexExists('requests', 'requests_agent_id_statut_index')) {
                    $table->index(['agent_id', 'statut']);
                }
            });
        }

        // Create activity logs table if not exists
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->json('data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('action');
                $table->index('created_at');
                $table->index('ip_address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indices from agents table
        Schema::table('agents', function (Blueprint $table) {
            $this->dropIndexIfExists('agents', 'agents_matricule_index');
            $this->dropIndexIfExists('agents', 'agents_email_index');
            $this->dropIndexIfExists('agents', 'agents_statut_index');
            $this->dropIndexIfExists('agents', 'agents_department_id_index');
            $this->dropIndexIfExists('agents', 'agents_province_id_index');
            $this->dropIndexIfExists('agents', 'agents_created_at_index');
            $this->dropIndexIfExists('agents', 'agents_statut_department_id_index');
        });

        // Drop indices from holidays table
        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                $this->dropIndexIfExists('holidays', 'holidays_agent_id_index');
                $this->dropIndexIfExists('holidays', 'holidays_statut_index');
                $this->dropIndexIfExists('holidays', 'holidays_date_debut_date_fin_index');
                $this->dropIndexIfExists('holidays', 'holidays_agent_id_statut_index');
            });
        }

        // Drop indices from pointages table
        if (Schema::hasTable('pointages')) {
            Schema::table('pointages', function (Blueprint $table) {
                $this->dropIndexIfExists('pointages', 'pointages_agent_id_index');
                $this->dropIndexIfExists('pointages', 'pointages_date_index');
                $this->dropIndexIfExists('pointages', 'pointages_agent_id_date_index');
            });
        }

        // Drop indices from documents table
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $this->dropIndexIfExists('documents', 'documents_agent_id_index');
                $this->dropIndexIfExists('documents', 'documents_created_at_index');
            });
        }

        // Drop indices from requests table
        if (Schema::hasTable('requests')) {
            Schema::table('requests', function (Blueprint $table) {
                $this->dropIndexIfExists('requests', 'requests_agent_id_index');
                $this->dropIndexIfExists('requests', 'requests_type_index');
                $this->dropIndexIfExists('requests', 'requests_statut_index');
                $this->dropIndexIfExists('requests', 'requests_agent_id_statut_index');
            });
        }

        // Drop activity_logs table
        Schema::dropIfExists('activity_logs');
    }

    /**
     * Check if an index exists
     */
    private function indexExists($table, $name)
    {
        $doctrine = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $doctrine->listTableIndexes($table);
        return array_key_exists($name, $indexes);
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists($table, $name)
    {
        if ($this->indexExists($table, $name)) {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        }
    }
};