<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE questions MODIFY type ENUM('single','multiple','truefalse','short_answer','fill_blank')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE questions MODIFY type ENUM('single','multiple','truefalse','short_answer')");
    }
};
