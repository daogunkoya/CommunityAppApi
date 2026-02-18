<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameEvent;
use App\Models\GameType;

class FixGameEventTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:fix-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix game events that have null or invalid game types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for game events with missing game types...');

        // Count events without game types
        $eventsWithoutType = GameEvent::whereNull('game_type_id')->count();
        $this->info("Found {$eventsWithoutType} events without game types");

        if ($eventsWithoutType > 0) {
            // Get the first available game type as default
            $defaultGameType = GameType::first();

            if ($defaultGameType) {
                $this->info("Assigning default game type: {$defaultGameType->name}");

                // Update events without game types
                $updated = GameEvent::whereNull('game_type_id')
                    ->update(['game_type_id' => $defaultGameType->id]);

                $this->info("Updated {$updated} events with default game type");
            } else {
                $this->error('No game types found in database!');
                return 1;
            }
        }

        // Check for game types with empty names
        $emptyNameTypes = GameType::whereNull('name')->orWhere('name', '')->count();
        $this->info("Found {$emptyNameTypes} game types with empty names");

        if ($emptyNameTypes > 0) {
            $this->warn('Game types with empty names found. Please check the game_types table.');
        }

        // Show summary
        $totalEvents = GameEvent::count();
        $validEvents = GameEvent::whereHas('gameType', function($q) {
            $q->whereNotNull('name')->where('name', '!=', '');
        })->count();

        $this->info("Summary:");
        $this->info("- Total events: {$totalEvents}");
        $this->info("- Events with valid game types: {$validEvents}");
        $this->info("- Events without valid game types: " . ($totalEvents - $validEvents));

        return 0;
    }
}


