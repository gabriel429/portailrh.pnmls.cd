<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'telephone_professionnel')) {
                $table->string('telephone_professionnel')->nullable()->after('telephone');
            }

            if (!Schema::hasColumn('agents', 'telephone_prive')) {
                $table->string('telephone_prive')->nullable()->after('telephone_professionnel');
            }
        });

        if (
            Schema::hasColumn('agents', 'telephone')
            && Schema::hasColumn('agents', 'telephone_professionnel')
        ) {
            DB::table('agents')
                ->whereNull('telephone_professionnel')
                ->whereNotNull('telephone')
                ->update(['telephone_professionnel' => DB::raw('telephone')]);
        }
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'telephone_prive')) {
                $table->dropColumn('telephone_prive');
            }

            if (Schema::hasColumn('agents', 'telephone_professionnel')) {
                $table->dropColumn('telephone_professionnel');
            }
        });
    }
};
