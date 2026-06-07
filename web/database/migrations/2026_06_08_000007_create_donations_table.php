<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 20);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee_amount');
            $table->unsignedBigInteger('payment_amount');
            $table->unsignedBigInteger('mosque_receives');
            $table->string('fee_mechanism', 30);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_anonymous')->default(false);
            $table->string('merchant_order_id', 50)->unique();
            $table->text('payment_url')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['mosque_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
