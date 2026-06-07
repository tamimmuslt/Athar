<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationAuthController;
use App\Http\Controllers\Admin\AdminOrganizationController;
use App\Http\Controllers\Volunteer\NotificationController;
use App\Http\Controllers\Volunteer\OrganizationController;

Route::prefix('volunteer')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::get('/organizations/{id}', [OrganizationController::class, 'show']);
});
Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'getProfile']);        
        Route::post('/profile/update', [AuthController::class, 'updateProfile']); 
        Route::get('/hours', [AuthController::class, 'getVolunteerHours']); 
        Route::get('/certificates', [AuthController::class, 'getCertificates']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
/*
مؤسسة
*/

Route::prefix('organization')->group(function () {
    Route::post('/register', [OrganizationAuthController::class, 'register']);
    Route::post('/login', [OrganizationAuthController::class, 'login']);
});


/*
admin
*/
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

