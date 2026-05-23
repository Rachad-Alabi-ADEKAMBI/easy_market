<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'salary_payment_date')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->date('salary_payment_date')->nullable()->after('salary');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'salary_payment_date')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('salary_payment_date');
        });
    }
};
