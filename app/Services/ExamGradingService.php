<?php

namespace App\Services;

use App\Enums\ExamRecordStatus;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\User;

class ExamGradingService
{
    /**
     * 自动评分客观题，简答题分配批改人
     */
    public function autoGrade(ExamRecord $record): void
    {
        $exam = $record->exam()->with('questions')->first();
        $answers = $record->answers ?? [];
        $objectiveScore = 0;
        $hasSubjective = false;

        $gradedAnswers = array_map(function ($answer) use ($exam, &$objectiveScore, &$hasSubjective) {
            $question = $exam->questions->firstWhere('id', $answer['question_id']);

            if (!$question) {
                return $answer;
            }

            // 简答题：需要导师/管理员手动批改
            if ($question->type == 4) {
                $hasSubjective = true;
                $answer['is_correct'] = null;
                $answer['score_awarded'] = 0;
                $answer['auto_graded'] = false;
                return $answer;
            }

            // 填空题：需要导师/管理员手动批改
            if ($question->type == 5) {
                $hasSubjective = true;
                $answer['is_correct'] = null;
                $answer['score_awarded'] = 0;
                $answer['auto_graded'] = false;
                return $answer;
            }

            $isCorrect = $this->checkAnswer($question, $answer['answer'] ?? '');
            $answer['is_correct'] = $isCorrect;
            $answer['score_awarded'] = $isCorrect ? (float) $question->score : 0;
            $objectiveScore += $answer['score_awarded'];

            return $answer;
        }, $answers);

        $record->answers = $gradedAnswers;
        $record->objective_score = $objectiveScore;

        if ($hasSubjective) {
            // 有简题 → 推送批改
            $record->status = ExamRecordStatus::PendingReview;
            $record->assigned_to = $this->resolveReviewer($record->user);
        } else {
            // 无简题 → 直接出分
            $record->status = ExamRecordStatus::Graded;
            $record->total_score = $objectiveScore;
            $record->graded_at = now();
        }

        $record->save();
    }

    /**
     * 解析批改人：
     * - 实习期员工 → 查找绑定导师，无导师则 null（等管理员分配）
     * - 正式员工 → 管理员
     */
    private function resolveReviewer(User $user): ?int
    {
        if ($user->isTrialEmployee()) {
            // 实习期：查找绑定导师
            $mentor = $user->mentors()
                ->where('mentor_student.status', 'active')
                ->first();

            return $mentor?->id; // 有导师返回导师ID，无导师返回null
        }

        // 正式员工：分配给管理员
        $admin = User::where('role', 'admin')->first();
        return $admin?->id;
    }

    /**
     * 管理员分配导师
     */
    public function assignReviewer(ExamRecord $record, User $reviewer, ?string $note): void
    {
        $record->assigned_to = $reviewer->id;
        $record->assignment_note = $note;
        $record->save();
    }

    /**
     * 导师/管理员批改主观题
     */
    public function mentorReview(ExamRecord $record, User $mentor, array $subjectiveScores, ?string $comment): void
    {
        $answers = $record->answers ?? [];
        $subjectiveTotal = 0;

        foreach ($answers as &$answer) {
            if (isset($subjectiveScores[$answer['question_id']])) {
                $score = (float) $subjectiveScores[$answer['question_id']];
                $answer['score_awarded'] = $score;
                $subjectiveTotal += $score;
            }
        }

        $record->answers = $answers;
        $record->subjective_score = $subjectiveTotal;
        $record->total_score = $record->objective_score + $subjectiveTotal;
        $record->status = ExamRecordStatus::Graded;
        $record->graded_at = now();
        $record->graded_by = $mentor->id;
        $record->mentor_comment = $comment;
        $record->save();
    }

    private function checkAnswer(Question $question, string|array $answer): bool
    {
        $correct = $question->correct_answer;

        return match ((int)$question->type) {
            1, 3 => strtolower(trim((string) $answer)) === strtolower(trim($correct)), // 单选、判断
            2 => $this->checkMultipleAnswer($correct, $answer), // 多选
            default => false,
        };
    }

    private function checkMultipleAnswer(string $correct, string|array $answer): bool
    {
        $correctSet = collect(explode(',', str_replace(' ', '', $correct)))->sort()->values();
        $answerSet = collect((array) $answer)->sort()->values();

        return $correctSet->toArray() === $answerSet->toArray();
    }
}
