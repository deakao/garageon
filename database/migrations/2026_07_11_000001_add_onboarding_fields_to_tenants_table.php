<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('onboarding_step', 40)->nullable()->after('trial_ends_at');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });

        DB::table('tenants')->whereNull('onboarding_completed_at')->update([
            'onboarding_completed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });
    }
};
