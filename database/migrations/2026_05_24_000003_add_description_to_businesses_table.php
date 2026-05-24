<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'description')) {
                $table->text('description')->nullable()->after('slogan');
            }

            if (! Schema::hasColumn('businesses', 'show_description_on_documents')) {
                $table->boolean('show_description_on_documents')->default(true)->after('show_slogan_on_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'show_description_on_documents')) {
                $table->dropColumn('show_description_on_documents');
            }

            if (Schema::hasColumn('businesses', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
