<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreferredFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facility_id',
        'facility_name',
        'facility_address',
        'latitude',
        'longitude',
        'membership_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
