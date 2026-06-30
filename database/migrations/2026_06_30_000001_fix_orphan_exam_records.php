<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $admin = DB::table('users')->where('role', 'admin')->first();
        if (!$admin) {
            // 测试环境无管理员时跳过数据修复（schema migration 不受影响）
            return;
        }

        // 1. 修复 assigned_to IS NULL 且 status = 3 (PendingReview) 的孤儿记录
        DB::table('exam_records')
            ->where('status', 3)
            ->whereNull('assigned_to')
            ->update(['assigned_to' => $admin->id]);

        // 2. 回退已自动出分的纯客观题记录（status = 4 且无主观题）
        // answers 是 JSON 列，格式：[{question_id, answer, is_correct, score_awarded, auto_graded}, ...]
        // questions 表有 exam_id 外键，直接查询该考试的题目类型
        $gradedRecords = DB::table('exam_records')
            ->where('status', 4)
            ->get();

        foreach ($gradedRecords as $record) {
            // 获取该考试的所有题目类型
            $questionTypes = DB::table('questions')
                ->where('exam_id', $record->exam_id)
                ->pluck('type')
                ->toArray();

            $hasSubjective = in_array('short_answer', $questionTypes) || in_array('fill_blank', $questionTypes);

            // 如果是纯客观题考试，回退为 PendingReview
            if (!$hasSubjective) {
                DB::table('exam_records')
                    ->where('id', $record->id)
                    ->update([
                        'status' => 3, // PendingReview
                        'assigned_to' => $admin->id,
                        'graded_at' => null,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // 回滚逻辑：将回退的记录恢复为 Graded 状态
        $admin = DB::table('users')->where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        DB::table('exam_records')
            ->where('status', 3)
            ->where('assigned_to', $admin->id)
            ->whereNull('graded_at')
            ->update([
                'status' => 4,
                'graded_at' => now(),
            ]);
    }
};
