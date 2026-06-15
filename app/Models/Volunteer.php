<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 

class Volunteer extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name', 'email', 'password', 'phone', 'city', 'bio', 'age',
        'gender', 'verification_code'
    ];

    protected $hidden = [
        'password', 'verification_code',
    ];

    // الحقل الجديد الحصري لطلبات المتطوع على الحملات والاختبارات
    public function applications()
    {
        return $this->hasMany(CampaignApplication::class, 'volunteer_id');
    }
}