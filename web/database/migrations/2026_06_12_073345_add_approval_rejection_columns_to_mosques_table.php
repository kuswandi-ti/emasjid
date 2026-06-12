<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds approval/rejection tracking columns to the mosques table.
     * Note: approved_at, rejection_reason, and invitation_code already exist
     * from the original create_mosques_table migration.
     * This migration adds: approved_by, rejected_at, rejected_by.
     */
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            // Track which owner approved the mosque
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Track when and by whom a mosque was rejected
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');

            $table->dropForeign(['rejected_by']);
            $table->dropColumn('rejected_by');

            $table->dropColumn('rejected_at');
        });
    }
};
