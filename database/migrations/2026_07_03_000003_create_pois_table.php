<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pois', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('price', 8, 2)->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('attributes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'latitude', 'longitude']);
        });

        Schema::create('category_poi', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poi_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'poi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_poi');
        Schema::dropIfExists('pois');
    }
};
