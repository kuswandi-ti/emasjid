<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('subuh');
            $table->time('subuh_iqomah')->nullable();
            $table->time('dzuhur');
            $table->time('dzuhur_iqomah')->nullable();
            $table->time('ashar');
            $table->time('ashar_iqomah')->nullable();
            $table->time('maghrib');
            $table->time('maghrib_iqomah')->nullable();
            $table->time('isya');
            $table->time('isya_iqomah')->nullable();
            $table->time('jumat_time')->nullable();
            $table->string('jumat_khatib', 255)->nullable();
            $table->string('jumat_imam', 255)->nullable();
            $table->timestamps();

            $table->index(['mosque_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
