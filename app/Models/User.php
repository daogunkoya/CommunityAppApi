<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'location',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'community_name',
        'borough',
        'location_verified',
        'gender',
        'date_of_birth',
        'bio',
        'phone',
        'profile_picture',
        'is_active',
        'email_verification_token',
        'email_verification_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'location_verified' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Generate email verification token.
     */
    public function generateEmailVerificationToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'email_verification_token' => $token,
            'email_verification_sent_at' => now(),
        ]);
        return $token;
    }

    /**
     * Get the communities that the user belongs to.
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'user_communities')
                    ->withPivot('is_primary', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get the user's primary community.
     */
    public function primaryCommunity()
    {
        return $this->communities()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get the user's active communities.
     */
    public function activeCommunities(): BelongsToMany
    {
        return $this->communities()->wherePivot('is_active', true);
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
     * Scope to get users by community.
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
     * Scope to get users within a radius.
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
     * Get nearby users.
     */
    public function getNearbyUsers(float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->latitude || !$this->longitude) {
            return collect();
        }

        return static::where('id', '!=', $this->id)
            ->where('is_active', true)
            ->withinRadius($this->latitude, $this->longitude, $radiusKm)
            ->get();
    }

    /**
     * Verify email with token.
     */
    public function verifyEmail(string $token): bool
    {
        if ($this->email_verification_token === $token) {
            $this->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'email_verification_sent_at' => null,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Check if email verification token is expired.
     */
    public function isEmailVerificationTokenExpired(): bool
    {
        if (!$this->email_verification_sent_at) {
            return true;
        }

        // Token expires after 24 hours
        return $this->email_verification_sent_at->addHours(24)->isPast();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function gameInterests()
    {
        return $this->belongsToMany(GameType::class, 'game_user_interest')->withPivot('skill_level');
    }

    public function joinedEvents()
    {
        return $this->belongsToMany(GameEvent::class, 'game_event_user')->withTimestamps();
    }
}
