<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_no' => 'required|string|max:50',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_no.required' => '请输入工号',
            'password.required' => '请输入密码',
            'password.min' => '密码长度不能少于6位',
        ];
    }
}
