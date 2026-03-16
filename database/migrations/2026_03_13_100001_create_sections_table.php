<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sections')) {
            return;
        }

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            // nullable : les services rattachés (type=service_rattache) n'appartiennent pas à un département
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['section', 'service_rattache'])
                  ->default('section')
                  ->comment('section = dans un département | service_rattache = directement sous SEN/SENA');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
