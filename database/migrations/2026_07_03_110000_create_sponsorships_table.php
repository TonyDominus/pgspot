<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('card');
            $table->string('partner_name');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('poi_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->string('image')->nullable();
            $table->string('placement')->default('home_sheet');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at', 'placement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorships');
    }
};
