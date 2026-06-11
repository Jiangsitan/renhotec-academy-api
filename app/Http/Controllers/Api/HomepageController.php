<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                $series = $category->series()
                    ->where('status', 'published')
                    ->withCount('publishedCourses as videos_count')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'description' => $s->description,
                        'cover_image' => $s->cover_image,
                        'videos_count' => $s->videos_count,
                    ]);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'series' => $series,
                ];
            });

        return response()->json(['data' => $categories]);
    }
}
