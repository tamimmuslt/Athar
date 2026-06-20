<?php

namespace App\Models;

// الكلاسات الأساسية لدعم التوكنات وعمليات تسجيل الدخول
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Volunteer extends Authenticatable
{
    // تفعيل التوكنات والإشعارات للمتطوع
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * الحقول القابلة للتعبئة (Mass Assignable)
     * تأكد من مطابقتها للحقول الموجودة عندك بالـ Migration
     */
   protected $fillable = [
        'full_name', 'email', 'password', 'phone', 'city', 'bio', 'age',
        'gender', 'verification_code'
    ];
    /**
     * الحقول المخفية عند تحويل الموديل إلى JSON
     */
   protected $hidden = [
        'password', 'verification_code',
    ];

    /**
     * تحويل أنواع البيانات (Casting)
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // تشفير تلقائي لكلمة المرور عند الحفظ
    ];

    /**
     * علاقة المتطوع مع أسئلة الكويز / طلبات التقديم
     */
    public function applications()
    {
        return $this->hasMany(CampaignApplication::class, 'volunteer_id');
    }
}