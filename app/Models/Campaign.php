<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

protected $fillable = [
        'organization_id', 
        'title', 
        'about',    // الـ about أو وصف الحملة
        'image', 
        'location', 
        'volunteers_needed', // المقاعد المطلوبة الكلية
        'volunteers_registered', // المقاعد المحجوزة فعلياً (الحقل الجديد)
        'start_date', 
        'end_date', 
        'time',           // وقت بدء الحملة (الحقل الجديد)
        'meeting_point',  // نقطة الالتقاء (الحقل الجديد)
        'latitude',       // الإحداثيات (الحقل الجديد)
        'longitude',      // الإحداثيات (الحقل الجديد)
        'type',           // نوع الحملة: on-ground أو remote (الحقل الجديد)
        'status' ,         // active, completed, cancelled, pending
        'category',               // النوع: volunteer أو donation
        'donation_goal',          // المبلغ المستهدف للحملة التبرعية
        'raised_amount',          // المبلغ الذي تم جمعه حتى الآن
        'donors_count',           // عدد المتبرعين
        'donation_benefits',      // فوائد وأوجه صرف التبرع (How Your Donation Helps)
        'responsibilities',
        'important_notes'
    ];

   public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function questions()
    {
        return $this->hasMany(CampaignQuestion::class);
    }

    public function applications()
    {
        return $this->hasMany(CampaignApplication::class);
    }
}