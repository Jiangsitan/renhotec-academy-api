<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('employee_no', $request->employee_no)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'employee_no' => ['工号或密码错误'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => '账户已被禁用或锁定，请联系管理员',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => '登录成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_no' => $user->employee_no,
                    'email' => $user->email,
                    'is_placeholder_email' => $user->is_placeholder_email ?? false,
                    'role' => $user->role->value,
                    'department' => $user->department,
                    'position' => $user->position,
                    'is_trial' => $user->isTrialEmployee(),
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '已退出登录']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_no' => $user->employee_no,
                'email' => $user->email,
                'is_placeholder_email' => $user->is_placeholder_email ?? false,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'department' => $user->department,
                'position' => $user->position,
                'hire_date' => $user->hire_date?->format('Y-m-d'),
                'trial_end_date' => $user->trial_end_date?->format('Y-m-d'),
                'is_trial' => $user->isTrialEmployee(),
            ],
        ]);
    }
}
