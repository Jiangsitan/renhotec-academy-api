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

            if ($question->type === 'short_answer') {
                $hasSubjective = true; // 所有简答题都需要导师审核
                $answer['is_correct'] = null; // 简答题不判断对错

                if ($question->correct_answer) {
                    // 有参考答案 → 自动计算分数
                    $autoScore = $this->calculateShortAnswerScore(
                        $question->correct_answer,
                        $answer['answer'] ?? '',
                        (float) $question->score
                    );
                    $answer['score_awarded'] = $autoScore;
                    $answer['auto_graded'] = true;
                } else {
                    // 无参考答案 → 等待人工批改
                    $answer['score_awarded'] = 0;
                    $answer['auto_graded'] = false;
                }

                return $answer;
            }

            // 填空题：需要导师审核
            if ($question->type === 'fill_blank') {
                $hasSubjective = true; // 标记需要审核

                if ($question->correct_answer) {
                    // 有参考答案 → 自动计算分数
                    $studentAnswer = $answer['answer'] ?? [];
                    $correctAnswer = json_decode($question->correct_answer, true) ?? [];
                    $score = $this->calculateFillBlankScore($correctAnswer, (array) $studentAnswer, (float) $question->score);
                    $answer['score_awarded'] = $score;
                    $answer['auto_graded'] = true;
                } else {
                    // 无参考答案 → 等待人工批改
                    $answer['score_awarded'] = 0;
                    $answer['auto_graded'] = false;
                }

                $answer['is_correct'] = null; // 填空题不判断对错，由导师审核
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

        return match ($question->type) {
            'single', 'truefalse' => strtolower(trim((string) $answer)) === strtolower(trim($correct)),
            'multiple' => $this->checkMultipleAnswer($correct, $answer),
            default => false,
        };
    }

    private function checkMultipleAnswer(string $correct, string|array $answer): bool
    {
        $correctSet = collect(explode(',', str_replace(' ', '', $correct)))->sort()->values();
        $answerSet = collect((array) $answer)->sort()->values();

        return $correctSet->toArray() === $answerSet->toArray();
    }

    /**
     * 逐字匹配计算简答题分数
     *
     * @param string $referenceAnswer 参考答案
     * @param string $studentAnswer 学生答案
     * @param float $totalScore 题目总分
     * @return float 四舍五入到最近 0.5 的分数
     */
    private function calculateShortAnswerScore(string $referenceAnswer, string $studentAnswer, float $totalScore): float
    {
        // 提取有效字符
        $refChars = $this->extractChars($referenceAnswer);
        $stuChars = $this->extractChars($studentAnswer);

        // 参考答案为空，返回 0
        if (empty($refChars)) {
            return 0;
        }

        // 计算命中率：参考答案中的字符在学生答案中出现的比例
        $matchCount = 0;
        $tempStuChars = $stuChars;

        foreach ($refChars as $refChar) {
            $index = array_search($refChar, $tempStuChars);
            if ($index !== false) {
                $matchCount++;
                unset($tempStuChars[$index]);
                $tempStuChars = array_values($tempStuChars);
            }
        }

        $hitRate = $matchCount / count($refChars);

        // 全命中给满分
        if ($hitRate >= 1.0) {
            return $totalScore;
        }

        // 按百分比计算，四舍五入到最近的 0.5
        $rawScore = $totalScore * $hitRate;

        return round($rawScore * 2) / 2;
    }

    /**
     * 提取文本中的有效字符（去除标点、空格、换行）
     */
    private function extractChars(string $text): array
    {
        // 去除标点、空格、换行、制表符
        $cleaned = preg_replace('/[\s\p{P}]/u', '', $text);
        // 转为小写
        $cleaned = mb_strtolower($cleaned, 'UTF-8');
        // 拆分为单个字符
        return preg_split('//u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 填空题按空位比例评分
     *
     * @param array $correctAnswers 正确答案数组
     * @param array $studentAnswers 学生答案数组
     * @param float $totalScore 题目总分
     * @return float 四舍五入到最近 0.5 的分数
     */
    private function calculateFillBlankScore(array $correctAnswers, array $studentAnswers, float $totalScore): float
    {
        if (empty($correctAnswers)) {
            return 0;
        }

        $blankCount = count($correctAnswers);
        $correctCount = 0;

        for ($i = 0; $i < $blankCount; $i++) {
            $correct = mb_strtolower(trim($correctAnswers[$i] ?? ''));
            $student = mb_strtolower(trim($studentAnswers[$i] ?? ''));

            if ($correct === $student) {
                $correctCount++;
            }
        }

        // 全对给满分
        if ($correctCount === $blankCount) {
            return $totalScore;
        }

        // 按比例计算，四舍五入到最近的 0.5
        $rawScore = $totalScore * ($correctCount / $blankCount);

        return round($rawScore * 2) / 2;
    }
}
