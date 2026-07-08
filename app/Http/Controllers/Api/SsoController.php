<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * SSO 控制器
 *
 * 处理 SSO 回调、登出、用户同步等功能
 */
class SsoController extends Controller
{
    protected SsoClientService $ssoClient;

    public function __construct(SsoClientService $ssoClient)
    {
        $this->ssoClient = $ssoClient;
    }

    /**
     * SSO 回调处理 - 浏览器重定向入口
     *
     * 流程：SSO中心重定向到此URL → 验证授权码 → 生成Sanctum Token → 重定向到前端
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->input('code');

        if (! $code) {
            return redirect(config('sso-client.redirect_after_login', '/'))
                ->withErrors(['error' => '缺少授权码']);
        }

        // 调用SSO中心平台校验授权码
        $result = $this->ssoClient->validateAuthCode($code);

        if (! $result) {
            return redirect(config('sso-client.redirect_after_login', '/'))
                ->withErrors(['error' => '授权码校验失败或已过期']);
        }

        $userInfo = $result['user'];

        // 查找或创建本地用户
        $user = $this->findOrCreateUser($userInfo);

        if (! $user) {
            return redirect(config('sso-client.redirect_after_login', '/'))
                ->withErrors(['error' => '用户创建失败']);
        }

        // 生成 Sanctum Token
        $token = $user->createToken('sso-auth-token')->plainTextToken;

        // 记录登录日志
        Log::info('SSO登录成功', [
            'sso_uid'  => $userInfo['sso_uid'] ?? null,
            'email'    => $userInfo['email'] ?? null,
            'user_id'  => $user->id,
            'ip'       => $request->ip(),
        ]);

        // 重定向到前端，携带 token
        $frontendUrl = config('sso-client.redirect_after_login', '/');
        $callbackUrl = rtrim(config('app.url', env('APP_URL', 'https://learn.renhotec.cn')), '/')
            . '/sso-callback?token=' . urlencode($token);

        return redirect($callbackUrl);
    }

    /**
     * SSO 登出
     *
     * 删除 Sanctum Token 并通知 SSO 中心
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // 删除当前 Sanctum Token
            $user->currentAccessToken()->delete();

            Log::info('SSO用户登出', [
                'user_id' => $user->id,
                'sso_uid' => $user->sso_uid,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '登出成功',
        ]);
    }

    /**
     * SSO 中心拉取当前系统所有用户数据
     *
     * POST /api/sso/users/pull
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pullUsers(Request $request): JsonResponse
    {
        $clientId     = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        // 验证 client_id 和 client_secret
        $validClientId     = config('sso-client.client_id');
        $validClientSecret = config('sso-client.client_secret');

        if (empty($clientId) || empty($clientSecret)
                             || $clientId     !== $validClientId
                             || $clientSecret !== $validClientSecret) {
            Log::warning('SSO用户拉取认证失败', [
                'client_id' => $clientId,
                'ip'        => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'client_id 或 client_secret 验证失败',
            ], 401);
        }

        // 确保所有用户都有 sso_uid
        $this->fillSsoUid();

        // 获取所有用户
        $users = User::all();

        $list = $users->map(function (User $user) {
            return [
                'sso_uid'  => $user->sso_uid,
                'email'    => $user->email,
                'name'     => $user->name,
                'active'   => $user->status === 'active',
                'password' => $user->password,
            ];
        })->values()->toArray();

        Log::info('SSO用户拉取完成', [
            'count' => count($list),
            'ip'    => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'list' => $list,
            ],
        ]);
    }

    /**
     * 将全部用户同步到 SSO 中心平台
     *
     * @return JsonResponse
     */
    public function syncAllUsers(): JsonResponse
    {
        $this->fillSsoUid();

        $stats = [
            'total'   => 0,
            'synced'  => 0,
            'skipped' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        $users = User::all();

        foreach ($users as $user) {
            $stats['total']++;

            if (empty($user->sso_uid)) {
                $stats['skipped']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'reason'  => 'sso_uid 为空，请先执行 fillSsoUid()',
                ];

                continue;
            }

            if (empty($user->email)) {
                $stats['skipped']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'reason'  => 'email 为空，无法同步',
                ];

                continue;
            }

            $payload = [
                'sso_uid'  => $user->sso_uid,
                'email'    => $user->email,
                'name'     => $user->name,
                'active'   => $user->status === 'active',
                'password' => $user->password,
            ];

            $ok = $this->ssoClient->syncUser($payload);

            if ($ok) {
                $stats['synced']++;
            } else {
                $stats['failed']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'reason'  => 'SSO 接口返回失败，详见日志',
                ];
            }
        }

        Log::info('SSO用户批量同步完成', $stats);

        return response()->json([
            'success' => true,
            'message' => '同步完成',
            'data'    => $stats,
        ]);
    }

    /**
     * 为 sso_uid 为空的用户生成唯一 UUID
     *
     * 使用 UUID v5（基于命名空间 + email 哈希）保证幂等性
     *
     * @return array
     */
    public function fillSsoUid(): array
    {
        $stats = [
            'total'   => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => [],
        ];

        $namespace = config('sso-client.sso_uuid_namespace', '6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        $users = User::where(function ($query) {
            $query->whereNull('sso_uid')->orWhere('sso_uid', '');
        })->get();

        foreach ($users as $user) {
            $stats['total']++;

            if (empty($user->email)) {
                $stats['skipped']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'reason'  => 'email 为空，无法生成 sso_uid',
                ];

                continue;
            }

            try {
                $ssoUid = Uuid::uuid5($namespace, $user->email)->toString();

                $user->sso_uid = $ssoUid;
                $user->save();

                $stats['updated']++;
            } catch (\Exception $e) {
                $stats['skipped']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'reason'  => $e->getMessage(),
                ];
                Log::error('生成 sso_uid 失败', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        Log::info('fillSsoUid 完成', $stats);

        return $stats;
    }

    /**
     * 查找或创建本地用户
     *
     * 优先通过 sso_uid 匹配，其次通过 email 匹配
     *
     * @param array $userInfo
     * @return User|null
     */
    protected function findOrCreateUser(array $userInfo): ?User
    {
        $ssoUid = $userInfo['sso_uid'] ?? null;
        $email  = $userInfo['email'] ?? null;

        // 1. 通过 sso_uid 查找
        if ($ssoUid) {
            $user = User::where('sso_uid', $ssoUid)->first();
            if ($user) {
                return $user;
            }
        }

        // 2. 通过 email 查找（兜底匹配）
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // 补充 sso_uid
                if (empty($user->sso_uid)) {
                    $user->sso_uid = $ssoUid;
                    $user->save();
                }

                return $user;
            }
        }

        // 3. 自动创建用户（如果开启）
        if (config('sso-client.auto_create_user', true) && $email) {
            try {
                $namespace = config('sso-client.sso_uuid_namespace', '6ba7b810-9dad-11d1-80b4-00c04fd430c8');
                $generatedSsoUid = $ssoUid ?? Uuid::uuid5($namespace, $email)->toString();

                $user = User::create([
                    'sso_uid'     => $generatedSsoUid,
                    'name'        => $userInfo['name'] ?? $email,
                    'email'       => $email,
                    'employee_no' => $userInfo['employee_no'] ?? '',
                    'password'    => bcrypt(''),
                    'role'        => 'student',
                    'status'      => 'active',
                ]);

                Log::info('SSO自动创建用户', [
                    'user_id' => $user->id,
                    'email'   => $email,
                    'sso_uid' => $generatedSsoUid,
                ]);

                return $user;
            } catch (\Exception $e) {
                Log::error('SSO自动创建用户失败', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }
}
