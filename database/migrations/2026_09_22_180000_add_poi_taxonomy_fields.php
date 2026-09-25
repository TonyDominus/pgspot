<?php

use App\Services\TaxonomyBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_indexable')->default(false)->after('is_active');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->boolean('is_filterable')->default(true)->after('is_active');
            $table->boolean('is_indexable')->default(false)->after('is_filterable');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_indexable');
        });

        Schema::table('pois', function (Blueprint $table) {
            $table->foreignId('primary_category_id')->nullable()->after('municipality_id')->constrained('categories')->nullOnDelete();
            $table->boolean('is_free')->nullable()->after('price');
            $table->string('accessibility', 16)->default('unknown')->after('is_free');
            $table->string('parking', 16)->default('unknown')->after('accessibility');
            $table->string('website')->nullable()->after('parking');
            $table->string('phone', 40)->nullable()->after('website');
            $table->string('source_type', 32)->nullable()->after('phone');
            $table->string('source_ref')->nullable()->after('source_type');
            $table->timestamp('last_verified_at')->nullable()->after('source_ref');
        });

        app(TaxonomyBackfillService::class)->run();
    }

    public function down(): void
    {
        Schema::table('pois', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_category_id');
            $table->dropColumn([
                'is_free',
                'accessibility',
                'parking',
                'website',
                'phone',
                'source_type',
                'source_ref',
                'last_verified_at',
            ]);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_filterable', 'is_indexable', 'sort_order']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_indexable');
        });
    }
};
