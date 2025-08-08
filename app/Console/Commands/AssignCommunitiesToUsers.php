<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Community;
use Illuminate\Support\Facades\DB;

class AssignCommunitiesToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-communities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign communities to existing users based on their location data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting community assignment for existing users...');

        $users = User::whereNull('community_name')
            ->orWhere('community_name', '')
            ->get();

        $this->info("Found {$users->count()} users without community assignment.");

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            try {
                // Try to assign community based on existing location data
                if ($this->assignCommunityToUser($user)) {
                    $assignedCount++;
                    $this->line("✓ Assigned community to user: {$user->email}");
                } else {
                    $skippedCount++;
                    $this->line("- Skipped user (no location data): {$user->email}");
                }
            } catch (\Exception $e) {
                $this->error("✗ Error assigning community to user {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("\nCommunity assignment completed!");
        $this->info("✓ Assigned: {$assignedCount} users");
        $this->info("- Skipped: {$skippedCount} users");
    }

    /**
     * Assign community to a user based on their location data
     */
    private function assignCommunityToUser(User $user): bool
    {
        // If user has community_name, try to find that community
        if ($user->community_name) {
            $community = Community::where('name', $user->community_name)
                ->where('city', $user->city ?? 'London')
                ->first();

            if ($community) {
                $this->attachUserToCommunity($user, $community);
                return true;
            }
        }

        // If user has borough, try to find that community
        if ($user->borough) {
            $community = Community::where('name', $user->borough)
                ->where('city', $user->city ?? 'London')
                ->first();

            if ($community) {
                $this->attachUserToCommunity($user, $community);
                return true;
            }
        }

        // If user has city, try to find a community in that city
        if ($user->city) {
            $community = Community::where('city', $user->city)
                ->where('type', 'city')
                ->first();

            if ($community) {
                $this->attachUserToCommunity($user, $community);
                return true;
            }
        }

        // Default to London boroughs if user is in London area
        if ($user->latitude && $user->longitude) {
            $closestCommunity = $this->findClosestCommunity($user->latitude, $user->longitude);
            if ($closestCommunity) {
                $this->attachUserToCommunity($user, $closestCommunity);
                return true;
            }
        }

        return false;
    }

    /**
     * Find the closest community to given coordinates
     */
    private function findClosestCommunity(float $latitude, float $longitude): ?Community
    {
        return Community::selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('distance', 'asc')
            ->first();
    }

    /**
     * Attach user to community
     */
    private function attachUserToCommunity(User $user, Community $community): void
    {
        // Check if user is already attached to this community
        if (!$user->communities()->where('community_id', $community->id)->exists()) {
            $user->communities()->attach($community->id, [
                'is_primary' => true,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // Update user's community_name and borough
        $user->update([
            'community_name' => $community->name,
            'borough' => $community->type === 'borough' ? $community->name : null,
            'city' => $community->city,
            'state' => $community->state,
            'country' => $community->country,
        ]);
    }
}
