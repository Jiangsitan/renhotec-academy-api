<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Notifications\CourseCompletedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningProgressController extends Controller
{
    public function syncProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'last_position_seconds' => 'required|integer|min:0',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'learning_time_delta' => 'required|integer|min:0|max:60',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($validated['course_id']);

        if (!in_array($course->type->value, ['video', 'document'])) {
            return response()->json(['message' => '此接口仅用于视频和文档课程进度同步'], 422);
        }

        $progress = LearningProgress::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->first();

        $alreadyCompleted = $progress && $progress->is_completed;

        // 构建更新数据
        $updateData = [
            'last_position_seconds' => $validated['last_position_seconds'],
            'progress_percentage' => $validated['progress_percentage'],
        ];

        // 已完成的课程不再更新学习时长
        if (!$alreadyCompleted) {
            // 累加学习时长，不超过 min_read_time
            $currentTime = $progress ? $progress->total_learning_time : 0;
            $newTime = $currentTime + intval($validated['learning_time_delta']);
            $maxTime = $course->min_read_time > 0 ? $course->min_read_time : PHP_INT_MAX;
            $updateData['total_learning_time'] = min($newTime, $maxTime);
        }

        if ($progress) {
            $progress->update($updateData);
        } else {
            $updateData['user_id'] = $user->id;
            $updateData['course_id'] = $validated['course_id'];
            $progress = LearningProgress::create($updateData);
        }

        return response()->json([
            'message' => '进度已同步',
            'data' => [
                'progress_percentage' => $progress->progress_percentage,
                'total_learning_time' => $progress->total_learning_time,
                'is_completed' => $progress->is_completed,
                'last_position_seconds' => $progress->last_position_seconds,
            ],
        ]);
    }

    public function completeDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'elapsed_time' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $course = Course::findOrFail($validated['course_id']);

        // 支持文档课程和视频课程（本地视频通过 DocumentReader 完成）
        if (!in_array($course->type->value, ['document', 'video'])) {
            return response()->json(['message' => '此接口仅用于文档和视频课程完成标记'], 422);
        }

        if ($validated['elapsed_time'] < $course->min_read_time) {
            return response()->json([
                'message' => '学习时间不足，需要至少 ' . $course->min_read_time . ' 秒',
                'required' => $course->min_read_time,
                'elapsed' => $validated['elapsed_time'],
            ], 422);
        }

        $progress = LearningProgress::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->first();

        // 已完成的课程不再更新学习时长
        if ($progress && $progress->is_completed) {
            return response()->json([
                'message' => '课程已完成',
                'data' => $progress,
            ]);
        }

        // 使用实际学习时间和最低阅读时长的较大值
        $learningTime = max($progress?->total_learning_time ?? 0, $course->min_read_time);

        if ($progress) {
            $progress->update([
                'is_completed' => true,
                'progress_percentage' => 100,
                'total_learning_time' => $learningTime,
                'completed_at' => now(),
            ]);
        } else {
            $progress = LearningProgress::create([
                'user_id' => $user->id,
                'course_id' => $validated['course_id'],
                'is_completed' => true,
                'progress_percentage' => 100,
                'total_learning_time' => $learningTime,
                'completed_at' => now(),
            ]);
        }

        // 发送课程完成通知
        $user->notify(new CourseCompletedNotification($course));

        return response()->json([
            'message' => '课程已完成',
            'data' => $progress,
        ]);
    }

    public function getProgress(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();

        $progress = LearningProgress::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        return response()->json(['data' => $progress]);
    }
}
