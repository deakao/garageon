<?php

use App\Models\Quote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('id');
        });

        Quote::query()->whereNull('public_token')->get()->each(function (Quote $quote) {
            $quote->forceFill(['public_token' => (string) Str::uuid()])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
