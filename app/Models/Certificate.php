<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
protected $fillable = [
    'volunteer_id', 'campaign_name', 'organization_name', 'date', 'certificate_file'
];

}
