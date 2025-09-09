<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:prune {--days= : Number of days to keep logs (default from config)}';

    /**
     * The console command description.
     */
    protected $description = 'Prune audit logs older than specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = $this->option('days') ?? config('activitylog.delete_records_older_than_days', 180);
        
        $cutoffDate = now()->subDays($retentionDays);
        
        $this->info("Pruning audit logs older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        
        $deletedCount = Activity::where('created_at', '<', $cutoffDate)->delete();
        
        $this->info("Deleted {$deletedCount} audit log entries.");
        
        return Command::SUCCESS;
    }
}
