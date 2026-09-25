<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('istat_code', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('name');
            // Lo slug non è globale: i futuri URL SEO non devono assumerlo come chiave nazionale.
            $table->string('slug');
            $table->string('code', 8)->nullable();
            $table->string('istat_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'slug']);
            $table->unique('code');
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->string('name');
            // Univoco nella provincia: in Italia esistono comuni omonimi in province diverse.
            $table->string('slug');
            $table->string('istat_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('intro')->nullable();
            $table->boolean('is_indexable')->default(false);
            $table->timestamps();

            $table->unique(['province_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('regions');
    }
};
