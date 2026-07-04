<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('eyebrow')->nullable();
            $table->string('hero_badge_title')->nullable();
            $table->string('hero_badge_body')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->text('analytics_head')->nullable();
            $table->text('conversion_pixel')->nullable();
            $table->text('custom_javascript')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'eyebrow',
                'hero_badge_title',
                'hero_badge_body',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'analytics_head',
                'conversion_pixel',
                'custom_javascript',
            ]);
        });
    }
};
