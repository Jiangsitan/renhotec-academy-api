<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SSO 客户端服务
 *
 * 与集团 SSO 中心平台通信
 */
class SsoClientService
{
    protected string $ssoUrl;

    protected string $clientId;

    protected string $clientSecret;

    protected int $timeout = 30;

    public function __construct()
    {
        $this->ssoUrl       = config('sso-client.sso_url', env('SSO_CENTER_URL'));
        $this->clientId     = config('sso-client.client_id', env('SSO_CLIENT_ID'));
        $this->clientSecret = config('sso-client.client_secret', env('SSO_CLIENT_SECRET'));
        $this->timeout      = config('sso-client.api_timeout', 30);
    }

    /**
     * 校验授权码并获取用户信息
     *
     * @param string $code 授权码（从URL参数获取）
     * @return array|null 成功返回用户信息数组，失败返回 null
     */
    public function validateAuthCode(string $code): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->ssoUrl}/api/sso/auth/validate-code", [
                    'code'          => $code,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    return $data['data'];
                }
            }

            Log::error('SSO授权码校验失败', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SSO授权码校验异常', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 验证 Access Token
     *
     * @param string $token
     * @return array|null
     */
    public function validateToken(string $token): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->ssoUrl}/api/sso/token/validate", [
                    'token' => $token,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    return $data['data'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SSO验证Token异常', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 刷新 Access Token
     *
     * @param string $refreshToken
     * @return array|null
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->ssoUrl}/api/sso/refresh", [
                    'refresh_token' => $refreshToken,
                    'app_code'      => $this->clientId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    return $data['data'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SSO刷新Token异常', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 同步单个用户到 SSO 中心平台
     *
     * @param array $userData 用户数据，至少包含 sso_uid、email、name、active
     * @return bool
     */
    public function syncUser(array $userData): bool
    {
        try {
            $payload = array_merge([
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ], $userData);

            $response = Http::timeout($this->timeout)
                ->post("{$this->ssoUrl}/api/sso/user/sync", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return ($data['success'] ?? false) === true;
            }

            Log::error('SSO用户同步失败', [
                'sso_uid' => $userData['sso_uid'] ?? null,
                'email'   => $userData['email']   ?? null,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SSO用户同步异常', [
                'sso_uid' => $userData['sso_uid'] ?? null,
                'email'   => $userData['email']   ?? null,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 登出（撤销Token）
     *
     * @param string $token
     * @return bool
     */
    public function logout(string $token): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post("{$this->ssoUrl}/api/sso/logout");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SSO登出异常', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 生成SSO登出URL
     *
     * @param string|null $redirectUri 登出后跳转地址
     * @return string
     */
    public function getLogoutUrl(?string $redirectUri = null): string
    {
        $url = "{$this->ssoUrl}/admin/logout";

        if ($redirectUri) {
            $url .= '?' . http_build_query(['redirect' => $redirectUri]);
        }

        return $url;
    }

    /**
     * 生成SSO授权跳转URL
     *
     * @param string|null $redirectUri 授权后回调地址
     * @return string
     */
    public function getAuthorizeUrl(?string $redirectUri = null): string
    {
        $params = [
            'client_id' => $this->clientId,
        ];

        if ($redirectUri) {
            $params['redirect_uri'] = $redirectUri;
        }

        return "{$this->ssoUrl}/admin/authorize?" . http_build_query($params);
    }

    /**
     * 获取客户端ID
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * 获取SSO中心平台地址
     */
    public function getSsoUrl(): string
    {
        return $this->ssoUrl;
    }
}
