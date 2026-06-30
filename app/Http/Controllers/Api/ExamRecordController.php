<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\AuditActionType;
use App\Enums\ExamRecordStatus;
use App\Models\Exam;
use App\Models\ExamCheat;
use App\Models\ExamRecord;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamRecordController extends Controller
{
    public function __construct(
        private ExamGradingService $gradingService,
        private AuditLogService $auditService,
    ) {}

    public function submit(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.answer' => 'nullable',
        ]);

        $user = $request->user();

        $existing = ExamRecord::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->whereIn('status', [
                ExamRecordStatus::Submitted,
                ExamRecordStatus::AutoGraded,
                ExamRecordStatus::PendingReview,
            ])
            ->first();

        // 如果有已批改但未通过的记录，标记为已重考
        if (!$existing) {
            $gradedRecord = ExamRecord::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->where('status', ExamRecordStatus::Graded)
                ->first();
            if ($gradedRecord && $exam->passing_score && $gradedRecord->total_score < $exam->passing_score) {
                $gradedRecord->update(['status' => ExamRecordStatus::Retaken]);
            } elseif ($gradedRecord) {
                $existing = $gradedRecord;
            }
        }

        if ($existing) {
            return response()->json(['message' => '您已提交过此考试', 'data' => $existing], 422);
        }

        $record = ExamRecord::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'answers' => $validated['answers'],
            'status' => ExamRecordStatus::Submitted,
            'submitted_at' => now(),
        ]);

        // 自动评分
        $this->gradingService->autoGrade($record);

        // 审计日志
        $this->auditService->log(
            user: $user,
            actionType: AuditActionType::SubmitExam,
            targetType: 'exam',
            targetId: $exam->id,
            request: $request
        );

        $record->load('exam');

        return response()->json([
            'message' => '答卷已提交',
            'data' => $record,
        ]);
    }

    public function show(Request $request, ExamRecord $examRecord): JsonResponse
    {
        $user = $request->user();

        if ($examRecord->user_id !== $user->id && $user->role->value !== 'admin') {
            $isMentor = $user->mentoredStudents()
                ->where('student_id', $examRecord->user_id)
                ->exists();

            if (!$isMentor) {
                return response()->json(['message' => '无权查看此记录'], 403);
            }
        }

        $examRecord->load(['exam.questions', 'user', 'grader']);

        // Normalize question types from MySQL ENUM strings to integers
        if ($examRecord->exam && $examRecord->exam->questions) {
            $examRecord->exam->questions->each(function ($q) {
                $q->type = self::normalizeQuestionType($q->type);
            });
        }

        return response()->json(['data' => $examRecord]);
    }

    public function wrongQuestions(Request $request, ExamRecord $examRecord): JsonResponse
    {
        $user = $request->user();

        if ($examRecord->user_id !== $user->id) {
            return response()->json(['message' => '无权查看此记录'], 403);
        }

        $examRecord->load('exam.questions.course');

        $wrongQuestions = collect($examRecord->answers ?? [])
            ->filter(fn($a) => $a['is_correct'] === false || $a['is_correct'] === null)
            ->map(function ($answer) use ($examRecord) {
                $question = $examRecord->exam->questions->firstWhere('id', $answer['question_id']);
                return [
                    'question_id' => $answer['question_id'],
                    'content' => $question?->content,
                    'type' => self::normalizeQuestionType($question?->type ?? ''),
                    'options' => $question?->options,
                    'your_answer' => $answer['answer'],
                    'correct_answer' => $question?->correct_answer,
                    'score_awarded' => $answer['score_awarded'] ?? 0,
                    'score' => $question?->score,
                    'course_id' => $question?->course_id,
                    'course_title' => $question?->course?->title,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'exam_record_id' => $examRecord->id,
                'exam_title' => $examRecord->exam->title,
                'total_score' => $examRecord->total_score,
                'wrong_questions' => $wrongQuestions,
            ],
        ]);
    }

    public function myRecords(Request $request): JsonResponse
    {
        $user = $request->user();

        $records = ExamRecord::with(['exam:id,title,passing_score'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $records]);
    }

    /**
     * MySQL ENUM 字符串 → 前端整数映射
     */
    private static function normalizeQuestionType(string|int|null $type): int
    {
        if ($type === null) return 0;
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

    public function recordCheat(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:leave_page,blur,exit_fullscreen',
            'detail' => 'nullable|string|max:500',
            'exam_record_id' => 'nullable|exists:exam_records,id',
        ]);

        $user = $request->user();

        ExamCheat::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'exam_record_id' => $validated['exam_record_id'] ?? null,
            'action' => $validated['action'],
            'detail' => $validated['detail'],
        ]);

        return response()->json(['message' => '已记录']);
    }
}
