<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TypingIndicator;
use Illuminate\Console\Command;

class CleanupTypingIndicators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typing:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired typing indicators';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = TypingIndicator::cleanupExpired();

        $this->info("Cleaned up {$deleted} expired typing indicators.");

        return 0;
    }
}



