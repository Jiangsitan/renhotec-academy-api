<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'student.active' => \App\Http\Middleware\EnsureStudentActive::class,
            'audit' => \App\Http\Middleware\AuditLogMiddleware::class,
            'auth.api' => \App\Http\Middleware\AuthenticateApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // 数据库完整性约束异常
        $exceptions->renderable(function (\Illuminate\Database\QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();

                if (str_contains($message, 'cannot be null')) {
                    return response()->json(['message' => '数据保存失败，必填字段不能为空'], 422);
                }
                if (str_contains($message, 'Duplicate entry')) {
                    return response()->json(['message' => '数据保存失败，存在重复数据'], 422);
                }

                return response()->json(['message' => '数据保存失败，请检查输入数据'], 422);
            }
        });

        // 模型未找到异常
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '数据不存在'], 404);
            }
        });

        // 404 异常
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '接口不存在'], 404);
            }
        });

        // 405 异常
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '请求方式错误'], 405);
            }
        });

        // 验证异常
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => '数据验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 认证异常
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '未认证，请先登录'], 401);
            }
        });

        // 授权异常
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '权限不足'], 403);
            }
        });

        // 限流异常
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '请求过于频繁，请稍后重试'], 429);
            }
        });

        // 其他未处理异常
        $exceptions->renderable(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => '服务器内部错误，请稍后重试'], 500);
            }
        });
    })->create();
