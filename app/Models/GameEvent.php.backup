<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Builder;

class GameEvent extends Model
{
    /** @use HasFactory<\Database\Factories\GameEventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'skill_level' => SkillLevel::class,
        'starts_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

  public function scopeMatchesUserSkill(Builder $query, User $user): Builder
{
    $interests = $user->gameInterests()
        ->get()
        ->pluck('pivot.skill_level', 'id'); // 'id' is game_type_id

    return $query->where(function ($q) use ($interests) {
        foreach ($interests as $gameTypeId => $userSkillLevel) {
            $level = $userSkillLevel instanceof \App\Enums\SkillLevel ? $userSkillLevel->value : $userSkillLevel;

            $q->orWhere(function ($subQ) use ($gameTypeId, $level) {
                $subQ->where('game_type_id', $gameTypeId)
                     ->where('skill_level', '<=', $level);
            });
        }
    });
}


    public function participants()
    {
        return $this->belongsToMany(User::class, 'game_event_participants')
            ->withPivot('is_waiting');
    }

    public function gameType(): BelongsTo
    {
        return $this->belongsTo(GameType::class);
    }

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get the short address string.
     */
    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }

    /**
     * Get the community location string.
     */
    public function getCommunityLocationAttribute(): string
    {
        if ($this->community_name && $this->borough) {
            return "{$this->community_name}, {$this->borough}";
        }

        if ($this->community_name) {
            return $this->community_name;
        }

        return $this->short_address;
    }

    /**
     * Scope to get events by community.
     */
    public function scopeByCommunity($query, string $communityName, string $city = null, string $state = null)
    {
        $query->where('community_name', $communityName);

        if ($city) {
            $query->where('city', $city);
        }

        if ($state) {
            $query->where('state', $state);
        }

        return $query;
    }

    /**
     * Scope to get events within a radius.
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
                *,
                ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Get nearby events.
     */
    public function getNearbyEvents(float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->latitude || !$this->longitude) {
            return collect();
        }

        return static::where('id', '!=', $this->id)
            ->where('starts_at', '>=', now())
            ->withinRadius($this->latitude, $this->longitude, $radiusKm)
            ->get();
    }
}
