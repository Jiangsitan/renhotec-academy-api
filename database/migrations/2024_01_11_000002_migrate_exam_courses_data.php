<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 迁移 course_id 不为 NULL 的数据到关联表
        DB::statement("
            INSERT INTO exam_courses (exam_id, course_id, created_at, updated_at)
            SELECT id, course_id, NOW(), NOW()
            FROM exams
            WHERE course_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::table('exam_courses')->truncate();
    }
};
