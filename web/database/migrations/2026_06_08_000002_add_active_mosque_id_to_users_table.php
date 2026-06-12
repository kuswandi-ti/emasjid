<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('active_mosque_id')->nullable()->after('remember_token');
            $table->foreign('active_mosque_id')->references('id')->on('mosques')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_mosque_id']);
            $table->dropColumn('active_mosque_id');
        });
    }
};
