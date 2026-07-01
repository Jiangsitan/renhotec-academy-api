<?php

namespace App\Services;

use App\Enums\ExamRecordStatus;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\User;

class ExamGradingService
{
    /**
     * 自动评分客观题，所有考试统一进入待批改状态
     */
    public function autoGrade(ExamRecord $record): void
    {
        $exam = $record->exam()->with('questions')->first();
        $answers = $record->answers ?? [];
        $objectiveScore = 0;

        $gradedAnswers = array_map(function ($answer) use ($exam, &$objectiveScore) {
            $question = $exam->questions->firstWhere('id', $answer['question_id']);

            if (!$question) {
                return $answer;
            }

            // 简答题：需要导师/管理员手动批改
            if ($question->type === 'short_answer') {
                $answer['is_correct'] = null;
                $answer['score_awarded'] = 0;
                $answer['auto_graded'] = false;
                return $answer;
            }

            // 填空题：需要导师/管理员手动批改
            if ($question->type === 'fill_blank') {
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

        // 所有考试统一进入待批改状态，由导师最终确认出分
        $record->status = ExamRecordStatus::PendingReview;
        $record->assigned_to = $this->resolveReviewer($record->user);

        $record->save();
    }

    /**
     * 解析批改人：
     * - 实习期员工 → 查找绑定导师，无导师则 fallback 到管理员
     * - 正式员工 → 管理员
     */
    private function resolveReviewer(User $user): int
    {
        if ($user->isTrialEmployee()) {
            // 实习期：查找绑定导师
            $mentor = $user->mentors()
                ->where('mentor_student.status', 'active')
                ->first();

            if ($mentor) {
                return $mentor->id;
            }
        }

        // 无导师或正式员工 → fallback 到管理员
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            throw new \RuntimeException('系统中无管理员用户，无法分配审批人');
        }
        return $admin->id;
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
     * 导师/管理员批改答卷（支持所有题型）
     */
    public function mentorReview(
        ExamRecord $record, 
        User $mentor, 
        array $scores,           // 所有题目的分数 {question_id: score}
        array $correctness,      // 所有题目的对错状态 {question_id: true/false}
        ?string $comment
    ): void {
        $answers = $record->answers ?? [];
        $objectiveTotal = 0;
        $subjectiveTotal = 0;
        
        // 加载题目以确定类型
        $exam = $record->exam()->with('questions')->first();
        
        foreach ($answers as &$answer) {
            $questionId = $answer['question_id'];
            $question = $exam->questions->firstWhere('id', $questionId);
            
            if (!$question) continue;
            
            // 更新分数（如果提供）
            if (isset($scores[$questionId])) {
                $answer['score_awarded'] = (float) $scores[$questionId];
            }
            
            // 更新对错状态（如果提供）
            if (isset($correctness[$questionId])) {
                $answer['is_correct'] = (bool) $correctness[$questionId];
            }
            
            // 标记为已手动评分
            $answer['auto_graded'] = true;
            
            // 根据题型累加分数
            if (in_array($question->type, ['single', 'multiple', 'truefalse'])) {
                $objectiveTotal += $answer['score_awarded'];
            } else {
                $subjectiveTotal += $answer['score_awarded'];
            }
        }
        
        $record->answers = $answers;
        $record->objective_score = $objectiveTotal;
        $record->subjective_score = $subjectiveTotal;
        $record->total_score = $objectiveTotal + $subjectiveTotal;
        $record->status = ExamRecordStatus::Graded;
        $record->graded_at = now();
        $record->graded_by = $mentor->id;
        $record->mentor_comment = $comment;
        $record->save();
    }

    private function checkAnswer(Question $question, string|array $answer): bool
    {
        $correct = $question->correct_answer;

        return match ($question->type) {
            'single', 'truefalse' => strtolower(trim((string) $answer)) === strtolower(trim($correct)),
            'multiple' => $this->checkMultipleAnswer($correct, $answer),
            default => false,
        };
    }

    private function checkMultipleAnswer(string $correct, string|array $answer): bool
    {
        $correctSet = collect(explode(',', str_replace(' ', '', $correct)))->sort()->values();

        // Handle both array and comma-separated string answers
        $answerStr = is_array($answer) ? implode(',', $answer) : $answer;
        $answerSet = collect(explode(',', str_replace(' ', '', $answerStr)))->sort()->values();

        return $correctSet->toArray() === $answerSet->toArray();
    }
}
