<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:prune-expired 
                            {--hours=24 : The number of hours to retain expired tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired Sanctum tokens';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $hours = (int)$this->option('hours');
        $cutoff = now()->subHours($hours);

        $query = PersonalAccessToken::where(function ($query) use ($cutoff) {
            $query->where('expires_at', '<', $cutoff)
                ->orWhere(function ($query) use ($cutoff) {
                    $query->whereNull('expires_at')
                        ->where('created_at', '<', $cutoff);
                });
        });

        $deletedCount = $query->count();
        $query->delete();

        $this->info("Successfully pruned {$deletedCount} expired tokens.");

        $veryOldCount = PersonalAccessToken::where('created_at', '<', now()->subDays(90))->delete();

        if ($veryOldCount > 0) {
            $this->info("Also deleted {$veryOldCount} very old tokens (90+ days).");
        }
    }
}
