<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('civility');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
        });

        DB::table('users')
            ->whereNull('first_name')
            ->orWhereNull('last_name')
            ->orderBy('id')
            ->get(['id', 'name', 'first_name', 'last_name'])
            ->each(function ($user) {
                $parts = preg_split('/\s+/', trim((string) $user->name), 2);

                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $user->first_name ?: ($parts[0] ?? null),
                    'last_name' => $user->last_name ?: ($parts[1] ?? null),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
