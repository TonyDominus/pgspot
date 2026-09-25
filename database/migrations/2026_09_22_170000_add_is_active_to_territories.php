<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('istat_code');
        });

        Schema::table('municipalities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
