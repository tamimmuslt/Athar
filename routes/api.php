<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignManagerController; // المسار الصحيح المباشر داخل Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationAuthController;
use App\Http\Controllers\Admin\AdminOrganizationController;
use App\Http\Controllers\Volunteer\NotificationController;
use App\Http\Controllers\Volunteer\OrganizationController;
use App\Http\Controllers\Volunteer\CampaignController;

/*
|--------------------------------------------------------------------------
| Volunteer Routes (Public)
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Profile & General
    Route::get('/profile', [AuthController::class, 'getProfile']);        
    Route::post('/profile/update', [AuthController::class, 'updateProfile']); 
    Route::get('/hours', [AuthController::class, 'getVolunteerHours']); 
    Route::get('/certificates', [AuthController::class, 'getCertificates']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    
    // Campaign Management (المؤسسة)
   
    // Campaigns (المتطوع)
    Route::get('/campaigns', [CampaignController::class, 'index']); 
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']); 
    Route::get('/campaigns/{id}/quiz', [CampaignController::class, 'getQuiz']); 
    Route::post('/campaigns/{id}/submit-quiz', [CampaignController::class, 'submitQuiz']); 
    Route::get('/my-applications', [CampaignController::class, 'myApplications']); 
});

/*
|--------------------------------------------------------------------------
| Organization Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('organization')->group(function () {
    Route::post('/register', [OrganizationAuthController::class, 'register']);
    Route::post('/login', [OrganizationAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/campaigns/store', [CampaignManagerController::class, 'store']);
        Route::get('/my-campaigns', [CampaignManagerController::class, 'index']); 
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/organizations/{id}/status', [AdminOrganizationController::class, 'updateStatus']);
    Route::get('/unread-notifications', function () {
        return auth()->user()->unreadNotifications;
    });
});