<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Series;
use App\Models\User;
use App\Notifications\LikeNotification;
use App\Notifications\MentionNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\ReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{
    public function index(Request $request, Series $series): JsonResponse
    {
        $perPage = $request->input('per_page', 20);

        $comments = Comment::with(['user', 'replies.user'])
            ->where('series_id', $series->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // 标记当前用户是否点赞
        $userId = $request->user()->id;
        $comments->getCollection()->transform(function ($comment) use ($userId) {
            $comment->is_liked = $comment->likes()->where('user_id', $userId)->exists();
            $comment->replies->each(function ($reply) use ($userId) {
                $reply->is_liked = $reply->likes()->where('user_id', $userId)->exists();
            });
            return $comment;
        });

        return response()->json(['data' => $comments]);
    }

    public function store(Request $request, Series $series): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $user = $request->user();

        $comment = Comment::create([
            'series_id' => $series->id,
            'user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        // 处理 @提及
        $this->processMentions($comment, $series, $user);

        // 发送通知
        if ($comment->parent_id) {
            $parentComment = Comment::find($comment->parent_id);
            if ($parentComment && $parentComment->user_id !== $user->id) {
                $parentComment->user->notify(new ReplyNotification($comment));
            }
        } else {
            $this->notifySeriesMentors($comment, $series, $user);
        }

        return response()->json([
            'message' => '评论发表成功',
            'data' => $comment->load('user'),
        ], 201);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => '只能编辑自己的评论'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => '评论已更新',
            'data' => $comment->load('user'),
        ]);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id && $request->user()->role->value !== 'admin') {
            return response()->json(['message' => '无权删除此评论'], 403);
        }

        $comment->delete();

        return response()->json(['message' => '评论已删除']);
    }

    public function toggleLike(Request $request, Comment $comment): JsonResponse
    {
        $userId = $request->user()->id;
        $existing = $comment->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('likes_count');
            $liked = false;
        } else {
            $comment->likes()->create(['user_id' => $userId]);
            $comment->increment('likes_count');
            $liked = true;

            // 发送点赞通知
            if ($comment->user_id !== $userId) {
                $comment->user->notify(new LikeNotification($comment, $request->user()));
            }
        }

        return response()->json([
            'data' => [
                'liked' => $liked,
                'likes_count' => $comment->fresh()->likes_count,
            ],
        ]);
    }

    public function mentionUsers(Request $request, Series $series): JsonResponse
    {
        $userId = $request->user()->id;

        // 获取系列下所有课程的导师 ID
        $courseMentorIds = Course::where('series_id', $series->id)
            ->whereNotNull('mentor_id')
            ->pluck('mentor_id')
            ->unique();

        // 获取系列下有学习进度的学员 ID
        $courseIds = Course::where('series_id', $series->id)->pluck('id');
        $studentIds = \App\Models\LearningProgress::whereIn('course_id', $courseIds)
            ->pluck('user_id')
            ->unique();

        // 合并并排除当前用户
        $userIds = $courseMentorIds->merge($studentIds)->unique()->filter(fn($id) => $id !== $userId);

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'employee_no')
            ->get();

        return response()->json(['data' => $users]);
    }

    private function notifySeriesMentors(Comment $comment, Series $series, User $user): void
    {
        $cacheKey = "comment_notify:{$user->id}:{$series->id}";

        // 检查是否在 1 小时内已经发过通知
        if (Cache::has($cacheKey)) {
            return;
        }

        // 获取系列下所有课程的导师
        $mentorIds = Course::where('series_id', $series->id)
            ->whereNotNull('mentor_id')
            ->pluck('mentor_id')
            ->unique();

        foreach ($mentorIds as $mentorId) {
            $mentor = User::find($mentorId);
            if ($mentor && $mentor->id !== $user->id) {
                $mentor->notify(new NewCommentNotification($comment));
            }
        }

        // 设置缓存，1 小时过期
        Cache::put($cacheKey, true, now()->addHour());
    }

    private function processMentions(Comment $comment, Series $series, User $user): void
    {
        preg_match_all('/@(\S+)/u', $comment->content, $matches);
        $mentions = $matches[1] ?? [];

        foreach ($mentions as $mention) {
            $mentionedUser = User::where('name', $mention)
                ->orWhere('employee_no', $mention)
                ->first();

            if ($mentionedUser && $mentionedUser->id !== $user->id) {
                $cacheKey = "mention_notify:{$user->id}:{$mentionedUser->id}:{$series->id}";

                if (!Cache::has($cacheKey)) {
                    $mentionedUser->notify(new MentionNotification($comment));
                    Cache::put($cacheKey, true, now()->addHour());
                }
            }
        }
    }
}
