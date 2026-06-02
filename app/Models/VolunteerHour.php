<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerHour extends Model
{
protected $fillable = [
    'volunteer_id', 'campaign_name', 'organization_name', 'hours', 'date', 'status'
];}
