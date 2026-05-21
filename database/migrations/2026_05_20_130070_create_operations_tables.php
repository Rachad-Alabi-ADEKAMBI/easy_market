<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 80);
            $table->string('type', 30)->default('variable');
            $table->unsignedInteger('amount');
            $table->date('spent_on');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('type', 30)->default('seller');
            $table->unsignedInteger('salary')->default(0);
            $table->date('hired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('gross_salary');
            $table->unsignedInteger('salary_advance')->default(0);
            $table->unsignedInteger('net_salary');
            $table->string('period');
            $table->string('status', 30)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->date('advanced_on');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 60);
            $table->string('title');
            $table->text('message');
            $table->string('channel', 30)->default('in_app');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('expenses');
    }
};
