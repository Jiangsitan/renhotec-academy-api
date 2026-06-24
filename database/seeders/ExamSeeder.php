<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 为第一个视频的考试添加题目
        $firstExam = DB::table('exams')->first();

        if (!$firstExam) return;

        $questions = [
            [
                'exam_id' => $firstExam->id,
                'course_id' => $firstExam->course_id,
                'type' => 1, // 单选题
                'content' => 'Renhotec 的核心价值观包括以下哪些？',
                'options' => json_encode([
                    ['key' => 'A', 'value' => '客户至上'],
                    ['key' => 'B', 'value' => '快速盈利'],
                    ['key' => 'C', 'value' => '持续创新'],
                    ['key' => 'D', 'value' => '个人英雄主义'],
                ]),
                'correct_answer' => 'A',
                'score' => 25,
                'sort_order' => 1,
            ],
            [
                'exam_id' => $firstExam->id,
                'course_id' => $firstExam->course_id,
                'type' => 2, // 多选题
                'content' => '以下哪些是公司提倡的工作态度？（多选）',
                'options' => json_encode([
                    ['key' => 'A', 'value' => '积极主动'],
                    ['key' => 'B', 'value' => '推诿责任'],
                    ['key' => 'C', 'value' => '团队协作'],
                    ['key' => 'D', 'value' => '持续学习'],
                ]),
                'correct_answer' => 'A,C,D',
                'score' => 25,
                'sort_order' => 2,
            ],
            [
                'exam_id' => $firstExam->id,
                'course_id' => $firstExam->course_id,
                'type' => 3, // 判断题
                'content' => '公司鼓励员工在工作中勇于创新、拥抱变化。',
                'options' => json_encode([
                    ['key' => 'A', 'value' => '正确'],
                    ['key' => 'B', 'value' => '错误'],
                ]),
                'correct_answer' => 'A',
                'score' => 25,
                'sort_order' => 3,
            ],
            [
                'exam_id' => $firstExam->id,
                'course_id' => $firstExam->course_id,
                'type' => 4, // 简答题
                'content' => '请简述你对公司企业文化的理解，以及你将如何在工作中践行。',
                'options' => null,
                'correct_answer' => '参考答案：企业文化是以客户为中心、持续创新、团队协作、追求卓越。在工作中应积极主动、与同事互助、不断学习提升。',
                'score' => 25,
                'sort_order' => 4,
            ],
        ];

        foreach ($questions as $q) {
            DB::table('questions')->insert(array_merge($q, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
