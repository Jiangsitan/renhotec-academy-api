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
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order');
            }])
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

                // 加载子分类的系列
                $children = $category->children->map(function ($child) {
                    $childSeries = $child->series()
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
                        'id' => $child->id,
                        'name' => $child->name,
                        'series' => $childSeries,
                    ];
                });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'series' => $series,
                    'children' => $children,
                ];
            });

        return response()->json(['data' => $categories]);
    }
}
