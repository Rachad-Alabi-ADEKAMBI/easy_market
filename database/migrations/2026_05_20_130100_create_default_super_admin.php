<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@easymarket.local'],
            [
                'name' => 'Super Admin',
                'phone' => '019622860',
                'password' => Hash::make('EasyMarket@2026'),
                'role' => 'super_admin',
                'is_active' => true,
                'can_edit_prices' => true,
            ]
        );
    }

    public function down(): void
    {
        User::where('email', 'admin@easymarket.local')->delete();
    }
};
