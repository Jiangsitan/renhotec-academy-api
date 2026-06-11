<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        // 支持通过 query 参数传递 token（用于 iframe/文件预览等场景）
        if (!$request->bearerToken() && $request->query('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => '未认证，请先登录'], 401);
        }

        // 将用户设置到 request 中
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
