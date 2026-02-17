<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'date',
        'venue_booked',
        'notes',
    ];

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
