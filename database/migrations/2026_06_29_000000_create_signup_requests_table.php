<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('business_name');
            $table->string('email');
            $table->string('whatsapp_phone');
            $table->string('business_type');
            $table->string('monthly_leads')->nullable();
            $table->string('main_challenge')->nullable();
            $table->string('status')->default('new');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signup_requests');
    }
};
