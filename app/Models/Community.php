<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'description',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that belong to this community.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_communities')
                    ->withPivot('is_primary', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get the game events in this community.
     */
    public function gameEvents(): HasMany
    {
        return $this->hasMany(GameEvent::class, 'community_name', 'name')
                    ->where('city', $this->city)
                    ->where('state', $this->state)
                    ->where('country', $this->country);
    }

    /**
     * Get the primary users of this community.
     */
    public function primaryUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_communities')
                    ->wherePivot('is_primary', true)
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    /**
     * Get the active users of this community.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_communities')
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }

    /**
     * Get the full location string.
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([$this->name, $this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }

    /**
     * Get the short location string.
     */
    public function getShortLocationAttribute(): string
    {
        $parts = array_filter([$this->name, $this->city]);
        return implode(', ', $parts);
    }

    /**
     * Scope to get active communities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get communities by city.
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope to get communities by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get communities within a radius.
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
                *,
                ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Get nearby communities.
     */
    public function getNearbyCommunities(float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->latitude || !$this->longitude) {
            return collect();
        }

        return static::active()
            ->where('id', '!=', $this->id)
            ->withinRadius($this->latitude, $this->longitude, $radiusKm)
            ->get();
    }

    /**
     * Get community statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->activeUsers()->count(),
            'primary_users' => $this->primaryUsers()->count(),
            'game_events' => $this->gameEvents()->count(),
            'recent_events' => $this->gameEvents()->where('starts_at', '>=', now())->count(),
        ];
    }
}
