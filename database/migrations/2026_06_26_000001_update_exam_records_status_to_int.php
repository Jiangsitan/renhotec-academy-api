<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 先将现有字符串值映射为数字
        DB::statement("UPDATE exam_records SET status = CASE status
            WHEN 'in_progress' THEN '0'
            WHEN 'submitted' THEN '1'
            WHEN 'auto_graded' THEN '2'
            WHEN 'pending_review' THEN '3'
            WHEN 'graded' THEN '4'
            WHEN 'rejected' THEN '5'
            ELSE '0'
        END");

        // 修改列为 TINYINT
        DB::statement("ALTER TABLE exam_records MODIFY status TINYINT NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        // 先改回 ENUM
        DB::statement("ALTER TABLE exam_records MODIFY status ENUM('in_progress','submitted','auto_graded','pending_review','graded','rejected') NOT NULL DEFAULT 'in_progress'");

        // 将数字映射回字符串
        DB::statement("UPDATE exam_records SET status = CASE status
            WHEN '0' THEN 'in_progress'
            WHEN '1' THEN 'submitted'
            WHEN '2' THEN 'auto_graded'
            WHEN '3' THEN 'pending_review'
            WHEN '4' THEN 'graded'
            WHEN '5' THEN 'rejected'
            ELSE 'in_progress'
        END");
    }
};
