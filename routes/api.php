<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignManagerController; 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationAuthController;
use App\Http\Controllers\Admin\AdminOrganizationController;
use App\Http\Controllers\Volunteer\NotificationController;
use App\Http\Controllers\Volunteer\OrganizationController;
use App\Http\Controllers\Volunteer\CampaignController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Api\CampaignReviewController;
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
        Route::get('/dashboard', [AuthController::class, 'getDashboardData']);        

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
    Route::get('/my-applications/{application_id}', [CampaignController::class, 'showApplication']);
    Route::post('campaigns/{id}/donate', [CampaignManagerController::class, 'makeDonation']);

    Route::middleware('auth:sanctum')->group(function () {
    // 1. جدول كل الحملات (التبويب الأول)
    Route::get('my-campaigns/all', [CampaignManagerController::class, 'allCampaigns']);
    
    // 2. جدول الحملات الحالية (التبويب الثاني)
    Route::get('my-campaigns/current', [CampaignManagerController::class, 'currentCampaigns']);
    
    // 3. جدول الحملات القادمة وحالة الطلب (التبويب الثالث)
    Route::get('my-campaigns/upcoming', [CampaignManagerController::class, 'upcomingCampaigns']);
    
    // 4. جدول الحملات المنتهية والتقييم (التبويب الرابع)
    Route::get('my-campaigns/completed', [CampaignManagerController::class, 'completedCampaigns']);
    
    // 5. جدول التبرعات المالية (التبويب الخامس)
    Route::get('my-campaigns/donations', [CampaignManagerController::class, 'donationCampaigns']);

    Route::post('reports/submit', [ReportController::class, 'store']);


        Route::post('notifications', [NotificationController::class, 'index']);

        Route::post('/campaigns/{id}/review', [CampaignReviewController::class, 'store']);

});
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