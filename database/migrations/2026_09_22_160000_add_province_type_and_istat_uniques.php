<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('type', 32)->default('province')->after('code');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->unique('istat_code');
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->unique('istat_code');
        });

        Schema::table('municipalities', function (Blueprint $table) {
            $table->unique('istat_code');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropUnique(['istat_code']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['istat_code']);
            $table->dropColumn('type');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->dropUnique(['istat_code']);
        });
    }
};
