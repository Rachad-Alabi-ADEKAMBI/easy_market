<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('civility', 10)->nullable();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('payment_terms')->nullable();
            $table->timestamps();
        });

        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_due');
            $table->unsignedInteger('amount_paid')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('current');
            $table->timestamps();
        });

        Schema::create('supplier_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_due');
            $table->unsignedInteger('amount_paid')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('current');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debts');
        Schema::dropIfExists('receivables');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
