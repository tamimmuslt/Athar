<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}