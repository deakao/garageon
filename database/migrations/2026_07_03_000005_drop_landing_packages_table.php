<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('landing_packages');
    }

    public function down(): void
    {
        // The packages CRUD was removed; keep rollback intentionally empty.
    }
};
