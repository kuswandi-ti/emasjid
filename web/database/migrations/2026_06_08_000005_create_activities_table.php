<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('speaker', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->date('start_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_note', 255)->nullable();
            $table->string('status', 20)->default('upcoming');
            $table->timestamps();

            $table->index(['mosque_id', 'status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
