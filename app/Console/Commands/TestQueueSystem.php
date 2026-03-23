<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteSyncTaskJob;
use App\Models\DatabaseConnection;
use App\Models\Project;
use App\Models\SyncTask;
use App\Services\SyncOrchestrator;
use Illuminate\Console\Command;

class TestQueueSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:test-sync {--demo : Run a demo without actual database connections}';

    /**
     * The console command description.
     */
    protected $description = 'Test the queue system with a simple sync task';

    /**
     * Execute the console command.
     */
    public function handle(SyncOrchestrator $orchestrator): int
    {
        $this->info('🚀 Testing Queue System for Multi-DB Sync Tool');
        $this->newLine();

        if ($this->option('demo')) {
            $this->runDemo();
            return self::SUCCESS;
        }

        // Check if we have any projects
        $projectCount = Project::count();
        if ($projectCount === 0) {
            $this->error('❌ No projects found. Please create a project first.');
            $this->info('💡 Run with --demo flag for a demonstration: php artisan queue:test-sync --demo');
            return self::FAILURE;
        }

        // Get first project
        $project = Project::first();
        $this->info("📁 Using project: {$project->name}");

        // Check database connections
        $dbCount = DatabaseConnection::where('project_id', $project->id)->count();
        if ($dbCount === 0) {
            $this->error('❌ No database connections found for this project.');
            $this->info('💡 Run with --demo flag for a demonstration: php artisan queue:test-sync --demo');
            return self::FAILURE;
        }

        $this->info("🗄️  Found {$dbCount} database connection(s)");
        $this->newLine();

        // Create a test sync task
        $syncTask = SyncTask::create([
            'project_id' => $project->id,
            'name' => 'Test Queue System',
            'description' => 'Testing the queue system and job batching',
            'task_type' => SyncTask::TYPE_RAW_QUERY,
            'up_query' => 'SELECT 1 as test',
            'down_query' => null,
            'status' => 'pending',
        ]);

        $this->info("✅ Created sync task: {$syncTask->id}");
        $this->info("📝 Task: {$syncTask->name}");
        $this->newLine();

        // Execute the sync task
        try {
            $this->info('🔄 Dispatching jobs to queue...');
            $batch = $orchestrator->executeSyncTask($syncTask);
            
            $this->info("✅ Batch created: {$batch->id}");
            $this->info("📊 Total jobs: {$batch->totalJobs}");
            $this->newLine();
            
            $this->info('💡 To monitor the queue, run: php artisan queue:listen');
            $this->info('📈 Check job_batches table for progress');
            $this->info('📋 Check sync_task_logs table for execution results');
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to dispatch: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Run a demonstration without actual connections.
     */
    protected function runDemo(): void
    {
        $this->info('📚 DEMO MODE: Multi-DB Sync Tool Queue System');
        $this->newLine();

        $this->line('🎯 <fg=cyan>System Architecture</>');
        $this->line('  • ExecuteSyncTaskJob: Processes individual database syncs');
        $this->line('  • SyncOrchestrator: Manages filtering, batching & dispatching');
        $this->line('  • Circuit Breaker: Halts at 25% failure rate');
        $this->line('  • Job Batching: Groups related jobs with progress tracking');
        $this->newLine();

        $this->line('📋 <fg=cyan>Components Created</>');
        $this->line('  ✅ app/Jobs/ExecuteSyncTaskJob.php');
        $this->line('  ✅ app/Services/SyncOrchestrator.php');
        $this->line('  ✅ Queue configuration with database driver');
        $this->line('  ✅ Job batching support');
        $this->newLine();

        $this->line('⚙️ <fg=cyan>Key Features</>');
        $this->line('  • Transaction-wrapped execution');
        $this->line('  • Automatic connection cleanup');
        $this->line('  • Detailed logging to sync_task_logs');
        $this->line('  • Retry support (3 attempts)');
        $this->line('  • 60-second timeout per job');
        $this->line('  • Circuit breaker at 25% failure threshold');
        $this->newLine();

        $this->line('🔄 <fg=cyan>Typical Workflow</>');
        $this->line('  1. Create SyncTask with filters');
        $this->line('  2. Orchestrator applies filters to get target DBs');
        $this->line('  3. Creates ExecuteSyncTaskJob for each DB');
        $this->line('  4. Dispatches jobs as a batch');
        $this->line('  5. Workers process jobs asynchronously');
        $this->line('  6. Results logged to sync_task_logs table');
        $this->line('  7. Batch callbacks update sync task status');
        $this->newLine();

        $this->line('🚀 <fg=cyan>Next Steps</>');
        $this->line('  • Create a project and database connections');
        $this->line('  • Run: php artisan queue:listen');
        $this->line('  • Create a sync task and dispatch');
        $this->line('  • Monitor progress in real-time');
        $this->newLine();

        $this->info('✨ Phase 3: Job System & Orchestration - COMPLETE!');
    }
}
