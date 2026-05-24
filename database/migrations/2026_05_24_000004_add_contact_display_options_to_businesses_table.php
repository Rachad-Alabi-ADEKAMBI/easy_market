<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'show_phone_on_documents')) {
                $table->boolean('show_phone_on_documents')->default(true)->after('show_description_on_documents');
            }

            if (! Schema::hasColumn('businesses', 'show_whatsapp_on_documents')) {
                $table->boolean('show_whatsapp_on_documents')->default(true)->after('show_phone_on_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'show_whatsapp_on_documents')) {
                $table->dropColumn('show_whatsapp_on_documents');
            }

            if (Schema::hasColumn('businesses', 'show_phone_on_documents')) {
                $table->dropColumn('show_phone_on_documents');
            }
        });
    }
};
