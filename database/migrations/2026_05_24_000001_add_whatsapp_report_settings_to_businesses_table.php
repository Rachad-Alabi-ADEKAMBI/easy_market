<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('whatsapp_reports_enabled')->default(false)->after('whatsapp_phone');
            $table->time('whatsapp_report_time')->nullable()->after('whatsapp_reports_enabled');
            $table->string('whatsapp_report_type', 40)->default('global')->after('whatsapp_report_time');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_reports_enabled',
                'whatsapp_report_time',
                'whatsapp_report_type',
            ]);
        });
    }
};
