<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\UserResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // Calculate distance if coordinates are available
        $distance = null;
        if ($user && $user->latitude && $user->longitude && $this->latitude && $this->longitude) {
            $distance = $this->calculateDistance(
                $user->latitude,
                $user->longitude,
                $this->latitude,
                $this->longitude
            );
        }

        // If distance was passed via additional data (e.g. from a query calculation), use it
        if (isset($this->distance)) {
            $distance = $this->distance;
        }

        return [
            'id' => $this->id,
            'title' => ($this->gameType && $this->gameType->name) ? $this->gameType->name . ' Game' : 'Game Event',
            'sport' => ($this->gameType && $this->gameType->name) ? $this->gameType->name : 'Unknown Sport',
            'location' => $this->location,
            'address' => $this->address,
            'city' => $this->city,
            'borough' => $this->borough,
            'community' => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'type' => $this->community->type,
                'full_location' => $this->community->full_location,
            ] : null,
            'distance_km' => $distance,
            'distance_formatted' => $distance !== null ? $this->formatDistance($distance) : null,
            'starts_at' => Carbon::parse($this->starts_at)->format('Y-m-d H:i'),
            'starts_at_relative' => Carbon::parse($this->starts_at)->diffForHumans(),
            'skill_level' => $this->skill_level->value ?? $this->skill_level, // Handle enum or raw value
            'skill_level_label' => $this->skill_level instanceof \App\Enums\SkillLevel ? $this->skill_level->label() : 'Unknown',
            'venue_booked' => (bool) $this->venue_booked,
            'notes' => $this->notes,
            'max_participants' => $this->max_participants,
            'current_participants' => $this->participants()->count(),
            'waiting_list_enabled' => (bool) $this->waiting_list_enabled,
            'is_full' => (bool) ($this->max_participants && $this->participants()->count() >= $this->max_participants),
            'organiser' => new UserResource($this->whenLoaded('organiser')),
            'participants' => $this->whenLoaded('participants', function () {
                return $this->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'avatar' => $participant->profile_picture,
                        'is_waiting' => (bool) $participant->pivot->is_waiting,
                    ];
                });
            }),
            'user_participation' => [
                'is_participating' => (bool) ($user ? $this->participants()->where('user_id', $user->id)->exists() : false),
                'is_waiting' => (bool) ($user ? $this->participants()->where('user_id', $user->id)->where('is_waiting', true)->exists() : false),
                'can_join' => (bool) ($user && !$this->participants()->where('user_id', $user->id)->exists() &&
                    (!$this->max_participants || $this->participants()->count() < $this->max_participants)),
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format distance for display
     */
    private function formatDistance(float $distance): string
    {
        $miles = $distance * 0.621371; // Convert km to miles

        if ($distance < 8) { // ~5 miles in km
            return '< 5 miles away';
        } elseif ($distance < 10) {
            return round($miles, 1) . ' miles';
        } else {
            return round($miles) . ' miles';
        }
    }
}
