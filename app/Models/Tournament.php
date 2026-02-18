<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GameType;

class Tournament extends Model
{
    /** @use HasFactory<\Database\Factories\TournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'game_type_id',
        'organiser_id',
        'location',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'starts_at',
        'ends_at',
        'registration_deadline',
        'max_participants',
        'min_participants',
        'entry_fee',
        'prize_pool',
        'prize_description',
        'skill_level',
        'status',
        'is_featured',
        'rules',
        'format',
        'bracket_type',
        'registration_enabled',
        'waiting_list_enabled',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'is_featured' => 'boolean',
        'registration_enabled' => 'boolean',
        'waiting_list_enabled' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function gameType(): BelongsTo
    {
        return $this->belongsTo(GameType::class);
    }

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_participants')
            ->withPivot(['registration_date', 'payment_status', 'is_waiting'])
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-500',
            'pending_approval' => 'bg-yellow-500',
            'approved' => 'bg-green-500',
            'rejected' => 'bg-red-500',
            'open' => 'bg-green-500',
            'filling-fast' => 'bg-orange-500',
            'almost-full' => 'bg-red-500',
            'registration-closed' => 'bg-gray-500',
            'in-progress' => 'bg-blue-500',
            'completed' => 'bg-purple-500',
            'cancelled' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }

    public function getSkillLevelLabelAttribute(): string
    {
        return match ($this->skill_level) {
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
            4 => 'Expert',
            default => 'All Levels',
        };
    }

    public function getCurrentParticipantsCountAttribute(): int
    {
        return $this->participants()->where('is_waiting', false)->count();
    }

    public function getWaitingListCountAttribute(): int
    {
        return $this->participants()->where('is_waiting', true)->count();
    }

    public function getIsFullAttribute(): bool
    {
        return $this->max_participants && $this->current_participants_count >= $this->max_participants;
    }

    public function getRegistrationProgressAttribute(): float
    {
        if (!$this->max_participants) return 0;
        return min(100, ($this->current_participants_count / $this->max_participants) * 100);
    }

    public function getDaysUntilDeadlineAttribute(): int
    {
        return now()->diffInDays($this->registration_deadline, false);
    }

    public function getDeadlineTextAttribute(): string
    {
        $days = $this->days_until_deadline;
        if ($days < 0) return 'Registration closed';
        if ($days === 0) return 'Today';
        if ($days === 1) return '1 day left';
        return "{$days} days left";
    }
}
