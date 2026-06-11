<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => '未认证，请先登录'], 401);
        }

        if ($user->role->value !== 'student') {
            return $next($request);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => '账户已被禁用或锁定，请联系管理员'], 403);
        }

        return $next($request);
    }
}
