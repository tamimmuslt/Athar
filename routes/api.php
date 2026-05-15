<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationAuthController;
use App\Http\Controllers\Admin\AdminOrganizationController;



Route::prefix('volunteer')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
    Route::post('/login', [AuthController::class, 'login']);
});
/*
مؤسسة
*/

Route::prefix('organization')->group(function () {
    Route::post('/register', [OrganizationAuthController::class, 'register']);
    Route::post('/login', [OrganizationAuthController::class, 'login']);
});

Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    
    // قبول أو رفض المؤسسة )
    Route::post('/organizations/{id}/status', [AdminOrganizationController::class, 'updateStatus']);
    
    // يمكنك لاحقاً إضافة عرض كل المؤسسات المعلقة هنا
    // Route::get('/organizations/pending', [AdminOrganizationController::class, 'getPending']);
});

Route::middleware('auth:sanctum')->get('/admin/unread-notifications', function () {
    return auth()->user()->unreadNotifications;
});