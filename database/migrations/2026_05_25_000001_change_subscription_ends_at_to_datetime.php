<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE subscriptions MODIFY ends_at DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE subscriptions MODIFY ends_at TIMESTAMP NULL');
    }
};
