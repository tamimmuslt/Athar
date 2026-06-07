<?php

namespace App\Models;

// أضفنا هذه الكلاسات لدعم التوكنات والإشعارات
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Campaign;
class Organization extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
 * تحديد الحقل الذي سيتم إرسال الإشعارات إليه.
 */
public function routeNotificationForMail($notification)
{
    // أخبر لارافيل أن يستخدم official_email بدلاً من email
    return $this->official_email;
}
    /**
     * الحقول القابلة للتعبئة (Mass Assignable)
     * بناءً على التصميم الذي أرفقته
     */
    protected $fillable = [
        'org_name',
        'official_email',
        'password',
        'phone_number',
        'address',
        'org_description',
        'verification_document',
        'status', // pending, approved, rejected
    ];

    /**
     * الحقول المخفية عند تحويل الموديل إلى JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تحويل أنواع البيانات (Casting)
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // لارافيل سيقوم بتشفيرها تلقائياً عند الحفظ
    ];

    /**
     * دالة مساعدة للتحقق إذا كانت المؤسسة مقبولة
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}