<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class MarkInactiveUsersOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:mark-inactive-offline {--minutes=5 : Minutes of inactivity before marking offline}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark users as offline if they have been inactive for a specified period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes');
        $cutoffTime = Carbon::now()->subMinutes($minutes);

        $inactiveUsers = User::where('is_online', true)
            ->where('last_seen_at', '<', $cutoffTime)
            ->get();

        if ($inactiveUsers->isEmpty()) {
            $this->info('No inactive users found.');
            return 0;
        }

        $count = 0;
        foreach ($inactiveUsers as $user) {
            $user->markAsOffline();
            $count++;
            $this->line("Marked {$user->full_name} as offline (last seen: {$user->last_seen_formatted})");
        }

        $this->info("Marked {$count} users as offline.");
        return 0;
    }
}
