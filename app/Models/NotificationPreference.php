<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'channel_push',
        'channel_email',
        'channel_sms',
        'frequency',
    ];

    protected $casts = [
        'channel_push' => 'boolean',
        'channel_email' => 'boolean',
        'channel_sms' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
