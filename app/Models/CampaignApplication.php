<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id', // ربطناه بالمتطوع حسب كودك
        'campaign_id',
        'score',
        'status',
        'submitted_at',
    ];

    // طلب التقديم ينتمي لمتطوع (Volunteer)
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    // طلب التقديم ينتمي لحملة معينة
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}