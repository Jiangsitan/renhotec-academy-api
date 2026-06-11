<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exam_records MODIFY status ENUM('in_progress','submitted','auto_graded','pending_review','graded','rejected')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exam_records MODIFY status ENUM('in_progress','submitted','auto_graded','pending_review','graded')");
    }
};
