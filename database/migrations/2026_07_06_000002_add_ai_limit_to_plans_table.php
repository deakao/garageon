<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Quantas mensagens o atendente virtual pode responder por dia neste plano.
            // Dimensionado para manter o custo de IA em até 20% do preço do plano.
            $table->unsignedInteger('ai_daily_message_limit')->default(50)->after('locations_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('ai_daily_message_limit');
        });
    }
};
