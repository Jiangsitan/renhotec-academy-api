<?php

use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SSO 回调路由（SSO中心平台浏览器重定向入口）
Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
