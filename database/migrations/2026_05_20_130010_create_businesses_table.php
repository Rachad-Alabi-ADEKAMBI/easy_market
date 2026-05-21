<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('address')->nullable();
            $table->string('ifu')->nullable();
            $table->string('slogan')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 20)->default('#3f8f7b');
            $table->string('whatsapp_phone', 30)->nullable();
            $table->boolean('show_logo_on_documents')->default(true);
            $table->boolean('show_ifu_on_documents')->default(true);
            $table->boolean('show_slogan_on_documents')->default(true);
            $table->boolean('show_address_on_documents')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
