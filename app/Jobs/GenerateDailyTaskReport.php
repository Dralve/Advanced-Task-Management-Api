<?php

namespace App\Jobs;

use App\Http\Resources\Task\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateDailyTaskReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $today = Carbon::today()->toDateString();
        Log::info('Generating daily task report for: ' . $today);

        $tasks = Task::whereDate('created_at', $today)->get();

        Cache::put('daily_tasks_' . $today, $tasks, now()->addHours(24)); // Cache for 24 hours

        Log::info('Number of tasks generated: ' . $tasks->count());
    }
}
