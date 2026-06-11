<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\MentorStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('sort_order')->get();
        return response()->json(['data' => $categories]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['category', 'series', 'mentor'])
            ->where('status', 'published');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('series_id')) {
            $query->where('series_id', $request->input('series_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->input('keyword') . '%');
        }

        $courses = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['data' => $courses]);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load(['category', 'series', 'mentor', 'attachments']);
        $course->load(['exams.courses']);

        $user = request()->user();
        $progress = $user->learningProgress()
            ->where('course_id', $course->id)
            ->first();

        // 获取关联的考试（取第一个）
        $exam = $course->exams->first();

        // 判断是否可以参加考试
        $canTakeExam = false;
        $incompleteCourses = [];

        if ($exam) {
            // 管理员始终可以
            if ($user->role->value === 'admin') {
                $canTakeExam = true;
            }
            // 导师：检查是否绑定其中任一课程
            elseif ($user->role->value === 'mentor') {
                $isBound = $exam->courses->contains('mentor_id', $user->id);
                if ($isBound) {
                    $canTakeExam = true;
                }
            }

            // 非管理员/导师，检查所有关联课程是否完成
            if (!$canTakeExam) {
                $examCourseIds = $exam->courses->pluck('id')->toArray();

                if (empty($examCourseIds)) {
                    $canTakeExam = true;
                } else {
                    $completedCourseIds = $user->learningProgress()
                        ->whereIn('course_id', $examCourseIds)
                        ->where('is_completed', true)
                        ->pluck('course_id')
                        ->toArray();

                    $canTakeExam = count($completedCourseIds) >= count($examCourseIds);

                    // 获取未完成的课程列表
                    if (!$canTakeExam) {
                        $incompleteCourses = $exam->courses
                            ->whereNotIn('id', $completedCourseIds)
                            ->map(fn($c) => ['id' => $c->id, 'title' => $c->title])
                            ->values()
                            ->toArray();
                    }
                }
            }
        }

        // 将 exam 附加到 course 返回
        $courseData = $course->toArray();
        $courseData['exam'] = $exam ? [
            'id' => $exam->id,
            'title' => $exam->title,
            'time_limit' => $exam->time_limit,
            'passing_score' => $exam->passing_score,
            'course_ids' => $exam->courses->pluck('id'),
        ] : null;

        return response()->json([
            'data' => [
                'course' => $courseData,
                'progress' => $progress,
                'can_take_exam' => $canTakeExam,
                'incomplete_courses' => $incompleteCourses,
            ],
        ]);
    }
}
