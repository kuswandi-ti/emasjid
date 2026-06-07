<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('amount');
            $table->string('description', 255);
            $table->string('category', 100)->nullable();
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mosque_id', 'type', 'transaction_date']);
            $table->index(['mosque_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
