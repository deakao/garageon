<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_service_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        DB::table('services')
            ->select('tenant_id', 'category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('tenant_id')
            ->orderBy('category')
            ->get()
            ->each(function (object $serviceCategory): void {
                DB::table('tenant_service_categories')->insert([
                    'tenant_id' => $serviceCategory->tenant_id,
                    'name' => $serviceCategory->category,
                    'slug' => Str::slug($serviceCategory->category),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_service_categories');
    }
};
