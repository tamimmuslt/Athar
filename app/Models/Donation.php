<?php

namespace App\Models; // الحرف الأول كابيتال دائماً

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id', // 🔥 الحقل المحدث
        'campaign_id',
        'amount',
        'payment_method',
        'optional_message',
        'status'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    // علاقة التبرع بالمتطوع (المتبرع الحالي)
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class, 'volunteer_id');
    }
}