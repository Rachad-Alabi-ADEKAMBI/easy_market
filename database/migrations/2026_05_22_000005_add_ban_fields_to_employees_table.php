<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'banned_at')) {
                $table->timestamp('banned_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('employees', 'ban_reason')) {
                $table->text('ban_reason')->nullable()->after('banned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'ban_reason')) {
                $table->dropColumn('ban_reason');
            }

            if (Schema::hasColumn('employees', 'banned_at')) {
                $table->dropColumn('banned_at');
            }
        });
    }
};
