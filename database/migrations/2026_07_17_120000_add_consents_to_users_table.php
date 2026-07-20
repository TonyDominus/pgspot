<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('accepted_terms_at')->nullable()->after('email_verified_at');
            $table->timestamp('accepted_privacy_at')->nullable()->after('accepted_terms_at');
            $table->boolean('notify_contributions')->default(false)->after('is_trusted_contributor');
            $table->boolean('notify_poi_updates')->default(false)->after('notify_contributions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_terms_at',
                'accepted_privacy_at',
                'notify_contributions',
                'notify_poi_updates',
            ]);
        });
    }
};
