<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('civility', 10)->nullable()->after('id');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('role', 30)->default('admin')->after('password');
            $table->boolean('can_edit_prices')->default(false)->after('role');
            $table->boolean('is_active')->default(true)->after('can_edit_prices');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'civility',
                'phone',
                'role',
                'can_edit_prices',
                'is_active',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
