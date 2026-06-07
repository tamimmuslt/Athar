<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'title', 'description', 'image', 'location_name', 'required_volunteers', 'start_date', 'end_date', 'status'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}