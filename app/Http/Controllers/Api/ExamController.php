<?php

namespace App\Http\Controllers\Api;

use App\Enums\ExamRecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\LearningProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Exam::with('courses')
            ->where('status', 'active');

        if ($request->filled('course_id')) {
            $query->whereHas('courses', function ($q) use ($request) {
                $q->where('courses.id', $request->input('course_id'));
            });
        }

        $exams = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        // 为每个考试添加权限状态
        $exams->getCollection()->transform(function ($exam) use ($user) {
            $examData = $exam->toArray();
            $canTake = $this->canTakeExam($user, $exam);
            $examData['can_take'] = $canTake;
            
            if ($canTake) {
                $examData['reason'] = null;
                $examData['incomplete_courses'] = [];
            } else {
                // 获取未完成的课程列表
                $courseIds = $exam->courses()->pluck('courses.id')->toArray();
                $completedCourseIds = LearningProgress::where('user_id', $user->id)
                    ->whereIn('course_id', $courseIds)
                    ->where('is_completed', true)
                    ->pluck('course_id')
                    ->toArray();
                
                $incompleteCourses = $exam->courses
                    ->whereNotIn('id', $completedCourseIds)
                    ->map(fn($c) => ['id' => $c->id, 'title' => $c->title])
                    ->values()
                    ->toArray();
                
                $examData['incomplete_courses'] = $incompleteCourses;
                $courseNames = collect($incompleteCourses)->pluck('title')->implode('、');
                $examData['reason'] = "需先完成以下课程：{$courseNames}";
            }
            
            return $examData;
        });

        return response()->json(['data' => $exams]);
    }

    public function show(Exam $exam): JsonResponse
    {
        if ($exam->status !== 'active') {
            return response()->json(['message' => '考试未开放'], 403);
        }

        $exam->load('questions', 'courses');
        $user = request()->user();

        // 检查权限
        $canTake = $this->canTakeExam($user, $exam);
        $existingRecord = $user->examRecords()
            ->where('exam_id', $exam->id)
            ->whereIn('status', [
                ExamRecordStatus::Submitted,
                ExamRecordStatus::AutoGraded,
                ExamRecordStatus::PendingReview,
            ])
            ->first();

        // 如果有已批改记录但未通过，视为可重考（不返回 existing_record）
        if (!$existingRecord) {
            $gradedRecord = $user->examRecords()
                ->where('exam_id', $exam->id)
                ->where('status', ExamRecordStatus::Graded)
                ->first();
            if ($gradedRecord && $exam->passing_score && $gradedRecord->total_score < $exam->passing_score) {
                // 未通过，允许重考
            } elseif ($gradedRecord) {
                $existingRecord = $gradedRecord;
            }
        }

        $questions = $exam->questions->map(function ($q) {
            return [
                'id' => $q->id,
                'type' => self::normalizeQuestionType($q->type),
                'content' => $q->content,
                'options' => $q->options,
                'score' => $q->score,
                'sort_order' => $q->sort_order,
            ];
        });

        return response()->json([
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'time_limit' => $exam->time_limit,
                    'passing_score' => $exam->passing_score,
                    'course_id' => $exam->courses->first()?->id,
                    'course_ids' => $exam->courses->pluck('id'),
                ],
                'questions' => $questions,
                'existing_record' => $existingRecord,
                'can_take' => $canTake,
            ],
        ]);
    }

    /**
     * MySQL ENUM 字符串 → 前端整数映射
     * 数据库存储 ENUM('single','multiple','truefalse','short_answer','fill_blank')
     * 但前端统一使用整数 1-5
     */
    private static function normalizeQuestionType(string|int $type): int
    {
        $map = [
            'single' => 1,
            'multiple' => 2,
            'truefalse' => 3,
            'short_answer' => 4,
            'fill_blank' => 5,
        ];

        if (is_int($type)) return $type;
        return $map[$type] ?? 0;
    }

    private function canTakeExam($user, Exam $exam): bool
    {
        // 管理员始终可以
        if ($user->role->value === 'admin') return true;

        $courseIds = $exam->courses()->pluck('courses.id')->toArray();

        // 没有关联课程，任何人都可以考
        if (empty($courseIds)) return true;

        // 检查是否所有关联课程都已完成
        $completedCount = LearningProgress::where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->where('is_completed', true)
            ->count();

        if ($completedCount >= count($courseIds)) return true;

        // 导师：检查是否绑定其中任一课程
        if ($user->role->value === 'mentor') {
            $isBound = Course::whereIn('id', $courseIds)
                ->where('mentor_id', $user->id)
                ->exists();
            if ($isBound) return true;
        }

        return false;
    }
}
