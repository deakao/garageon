<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_attendants', function (Blueprint $table) {
            // Quando ligado, o atendente cria agendamentos como "pending" para a
            // loja confirmar, em vez de confirmar direto ("scheduled").
            $table->boolean('require_booking_confirmation')->default(false)->after('context');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_attendants', function (Blueprint $table) {
            $table->dropColumn('require_booking_confirmation');
        });
    }
};
