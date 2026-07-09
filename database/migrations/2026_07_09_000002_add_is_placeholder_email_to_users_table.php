<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add the column if it doesn't exist (SQLite migration may have already added it)
        if (!Schema::hasColumn('users', 'is_placeholder_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_placeholder_email')->default(false)->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_placeholder_email');
        });
    }
};
