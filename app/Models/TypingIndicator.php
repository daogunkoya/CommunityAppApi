<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TypingIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'context_id',
        'context_type',
        'started_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'context_id')->where('context_type', 'conversation');
    }

    public function discussion()
    {
        return $this->belongsTo(\App\Models\Discussion::class, 'context_id')->where('context_type', 'discussion');
    }

    public function isActive()
    {
        return $this->expires_at->isFuture();
    }

        public static function startTyping($userId, $contextId, $contextType = 'conversation')
    {
        $expiresAt = now()->addSeconds(30); // Typing indicator expires after 30 seconds for better testing

        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'context_id' => $contextId,
                'context_type' => $contextType,
            ],
            [
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    public static function stopTyping($userId, $contextId, $contextType = 'conversation')
    {
        return static::where('user_id', $userId)
            ->where('context_id', $contextId)
            ->where('context_type', $contextType)
            ->delete();
    }

    public static function getActiveTypingUsers($contextId, $contextType = 'conversation')
    {
        return static::where('context_id', $contextId)
            ->where('context_type', $contextType)
            ->where('expires_at', '>', now())
            ->with('user:id,name,full_name')
            ->get()
            ->map(function ($indicator) {
                return [
                    'user_id' => $indicator->user_id,
                    'user_name' => $indicator->user->full_name ?? $indicator->user->name,
                    'started_at' => $indicator->started_at,
                ];
            });
    }

    public static function cleanupExpired()
    {
        return static::where('expires_at', '<=', now())->delete();
    }
}
