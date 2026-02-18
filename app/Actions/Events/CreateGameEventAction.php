<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\City;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateGameEventAction
{
    public function execute(array $data, User $user): GameEvent
    {
        return DB::transaction(function () use ($data, $user) {
            // Assign community if location data is provided
            $communityId = null;
            $communityName = $data['community_name'] ?? $data['borough'] ?? null;

            if ($communityName) {
                $city = $data['city'] ?? 'London';
                $state = $data['state'] ?? 'England';
                $country = $data['country'] ?? 'UK';

                $community = Community::firstOrCreate(
                    [
                        'name' => $communityName,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                    ],
                    [
                        'type' => 'borough',
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                        'description' => "Community in {$city}, {$state}",
                        'is_active' => true,
                    ]
                );

                $communityId = $community->id;
            }

            $eventData = array_merge($data, [
                'organiser_id' => $user->id,
                'community_id' => $communityId,
            ]);

            // Remove non-model attributes
            unset($eventData['community_name']);

            $event = GameEvent::create($eventData);

            // Auto-join the organiser
            $event->participants()->attach($user->id, ['is_waiting' => false]);

            // Auto-create community conversation if community exists
            if ($communityId) {
                $conversation = Conversation::firstOrCreate(
                    [
                        'type' => 'community',
                        'context_id' => $communityId,
                    ],
                    [
                        'name' => ($communityName ?? 'Community') . ' - Community Chat',
                    ]
                );

                // Add the organiser to the conversation if not already in
                if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
                    $conversation->participants()->attach($user->id);
                }
            }

            return $event;
        });
    }
}
