<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('type', 30)->default('invoice');
            $table->string('payment_method', 30)->default('cash');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->date('credit_due_date')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 12, 2);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('sale');
            $table->string('method', 30)->default('cash');
            $table->unsignedInteger('amount');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
