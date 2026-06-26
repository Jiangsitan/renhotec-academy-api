<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Series;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Series::withCount('publishedCourses as videos_count')
            ->where('status', 'published');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $series = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 12));

        return response()->json(['data' => $series]);
    }

    public function show(Series $series): JsonResponse
    {
        if ($series->status !== 'published') {
            return response()->json(['message' => '系列未发布'], 403);
        }

        $series->load(['publishedCourses.exam', 'publishedCourses.attachments', 'publishedCourses.mentor', 'category.parent']);

        $user = request()->user();

        $coursesWithProgress = $series->publishedCourses->map(function ($course) use ($user) {
            $progress = $user->learningProgress()
                ->where('course_id', $course->id)
                ->first();

            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'type' => $course->type->value,
                'duration' => $course->duration,
                'sort_order' => $course->sort_order,
                'has_exam' => $course->exams()->exists(),
                'content_url' => $course->content_url,
                'attachments' => $course->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'file_name' => $a->file_name,
                    'file_path' => $a->file_path,
                    'file_size' => $a->file_size,
                    'download_count' => $a->download_count,
                ]),
                'mentor' => $course->mentor ? [
                    'id' => $course->mentor->id,
                    'name' => $course->mentor->name,
                ] : null,
                'progress' => $progress ? [
                    'is_completed' => $progress->is_completed,
                    'progress_percentage' => $progress->progress_percentage,
                    'total_learning_time' => $progress->total_learning_time,
                ] : null,
            ];
        });

        return response()->json([
            'data' => [
                'series' => [
                    'id' => $series->id,
                    'name' => $series->name,
                    'description' => $series->description,
                    'cover_image' => $series->cover_image,
                    'category' => $series->category,
                    'videos_count' => $series->publishedCourses->count(),
                ],
                'videos' => $coursesWithProgress,
            ],
        ]);
    }
}
