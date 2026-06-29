<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return; // SQLite doesn't support ALTER TABLE MODIFY
        }

        DB::statement("ALTER TABLE questions MODIFY type ENUM('single','multiple','truefalse','short_answer','fill_blank')");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE questions MODIFY type ENUM('single','multiple','truefalse','short_answer')");
    }
};
