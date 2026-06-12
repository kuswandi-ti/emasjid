<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes to the mosques table for owner dashboard queries.
     * Note: index on 'status' already exists from create_mosques_table migration.
     * This migration adds the missing created_at index and composite status+created_at index.
     */
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            // Index on created_at for sorting pending mosques by oldest first
            $table->index('created_at');

            // Composite index on status + created_at for filtered + sorted queries
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
