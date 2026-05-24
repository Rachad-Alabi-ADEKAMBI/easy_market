<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('canceled_by')->nullable()->after('seller_id')->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable()->after('sold_at');
            $table->text('cancellation_reason')->nullable()->after('canceled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['canceled_by']);
            $table->dropColumn(['canceled_by', 'canceled_at', 'cancellation_reason']);
        });
    }
};
