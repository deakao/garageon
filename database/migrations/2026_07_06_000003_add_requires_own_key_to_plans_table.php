<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Plano "traga sua própria chave": atendente ilimitado, mas exige a
            // API key do tenant (o custo de IA é dele, não da plataforma).
            $table->boolean('requires_own_key')->default(false)->after('ai_daily_message_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('requires_own_key');
        });
    }
};
