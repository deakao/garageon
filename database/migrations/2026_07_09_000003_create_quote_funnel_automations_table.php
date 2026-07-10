<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_funnel_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 32);
            $table->string('channel', 16);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('subject')->nullable();
            $table->text('message_template');
            $table->timestamps();

            $table->unique(['tenant_id', 'stage', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_funnel_automations');
    }
};
