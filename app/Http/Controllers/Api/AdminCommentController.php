<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Notifications\ReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::with(['user', 'series', 'parent.user']);

        if ($request->filled('series_id')) {
            $query->where('series_id', $request->input('series_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('content', 'like', "%{$keyword}%");
        }

        $comments = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json(['data' => $comments]);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => '评论已更新',
            'data' => $comment->load('user'),
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['message' => '评论已删除']);
    }

    public function reply(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $reply = Comment::create([
            'series_id' => $comment->series_id,
            'user_id' => $user->id,
            'parent_id' => $comment->id,
            'content' => $validated['content'],
        ]);

        // 通知被回复的用户
        if ($comment->user_id !== $user->id) {
            $comment->user->notify(new ReplyNotification($reply));
        }

        return response()->json([
            'message' => '回复成功',
            'data' => $reply->load('user'),
        ], 201);
    }
}
