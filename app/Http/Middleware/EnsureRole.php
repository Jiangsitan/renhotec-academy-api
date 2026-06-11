<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => '未认证，请先登录'], 401);
        }

        if (!in_array($user->role->value, $roles)) {
            return response()->json(['message' => '权限不足'], 403);
        }

        return $next($request);
    }
}
