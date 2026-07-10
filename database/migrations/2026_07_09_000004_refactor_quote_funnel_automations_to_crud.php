<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_funnel_automations', function (Blueprint $table) {
            $table->string('name')->default('')->after('tenant_id');
            $table->unsignedInteger('delay_value')->default(0)->after('is_active');
            $table->string('delay_unit', 16)->default('minutes')->after('delay_value');
            $table->index('tenant_id', 'quote_funnel_automations_tenant_id_index');
        });

        $stageLabels = [
            'sent' => 'Enviado',
            'pending' => 'Aguardando',
            'accepted' => 'Aceito',
            'expired' => 'Expirado',
        ];

        foreach (DB::table('quote_funnel_automations')->orderBy('id')->get() as $row) {
            $channelLabel = $row->channel === 'email' ? 'E-mail' : 'WhatsApp';
            $stageLabel = $stageLabels[$row->stage] ?? $row->stage;

            DB::table('quote_funnel_automations')->where('id', $row->id)->update([
                'name' => "{$channelLabel} · {$stageLabel}",
                'delay_value' => (int) $row->delay_minutes,
                'delay_unit' => 'minutes',
            ]);
        }

        Schema::table('quote_funnel_automations', function (Blueprint $table) {
            $table->dropUnique('quote_funnel_automations_tenant_id_stage_channel_unique');
            $table->dropColumn('delay_minutes');
            $table->index(['tenant_id', 'stage', 'is_active'], 'quote_funnel_automations_tenant_stage_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('quote_funnel_automations', function (Blueprint $table) {
            $table->unsignedInteger('delay_minutes')->default(0)->after('is_active');
        });

        foreach (DB::table('quote_funnel_automations')->orderBy('id')->get() as $row) {
            $minutes = match ($row->delay_unit) {
                'hours' => (int) $row->delay_value * 60,
                'days' => (int) $row->delay_value * 1440,
                default => (int) $row->delay_value,
            };

            DB::table('quote_funnel_automations')->where('id', $row->id)->update([
                'delay_minutes' => $minutes,
            ]);
        }

        Schema::table('quote_funnel_automations', function (Blueprint $table) {
            $table->dropIndex('quote_funnel_automations_tenant_stage_active_index');
            $table->dropIndex('quote_funnel_automations_tenant_id_index');
            $table->dropColumn(['name', 'delay_value', 'delay_unit']);
            $table->unique(['tenant_id', 'stage', 'channel']);
        });
    }
};
