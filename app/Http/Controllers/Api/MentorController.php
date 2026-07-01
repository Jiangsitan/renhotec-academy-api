<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\ExamRecordStatus;
use App\Models\ExamRecord;
use App\Services\ExamGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function __construct(private ExamGradingService $gradingService)
    {
    }

    public function pendingReviews(Request $request): JsonResponse
    {
        $mentor = $request->user();

        // 查看分配给自己的待批改答卷
        $records = ExamRecord::with(['user:id,name,employee_no,department', 'exam:id,title'])
            ->where('assigned_to', $mentor->id)
            ->where('status', ExamRecordStatus::PendingReview)
            ->orderBy('submitted_at', 'asc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $records]);
    }

    public function reviewedRecords(Request $request): JsonResponse
    {
        $mentor = $request->user();

        // 查看自己已批改的答卷（已批改 + 已驳回）
        $records = ExamRecord::with(['user:id,name,employee_no,department', 'exam:id,title,passing_score'])
            ->where('graded_by', $mentor->id)
            ->whereIn('status', [ExamRecordStatus::Graded, ExamRecordStatus::Rejected])
            ->orderBy('graded_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $records]);
    }

    public function review(Request $request, ExamRecord $examRecord): JsonResponse
    {
        $validated = $request->validate([
            'scores' => 'nullable|array',
            'scores.*' => 'required|numeric|min:0',
            'correctness' => 'nullable|array',
            'correctness.*' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
            'action' => 'required|in:approve',
        ]);

        $mentor = $request->user();

        // 检查是否有权限批改（分配给自己或是管理员）
        if ($examRecord->assigned_to !== $mentor->id && $mentor->role->value !== 'admin') {
            return response()->json(['message' => '无权批改此答卷'], 403);
        }

        if ($examRecord->status !== ExamRecordStatus::PendingReview) {
            return response()->json(['message' => '此答卷不需要批改'], 422);
        }

        $scores = $validated['scores'] ?? [];
        $correctness = $validated['correctness'] ?? [];

        // 通过逻辑
        $this->gradingService->mentorReview(
            $examRecord,
            $mentor,
            $scores,
            $correctness,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => '批改完成',
            'data' => $examRecord->fresh(),
        ]);
    }
}
