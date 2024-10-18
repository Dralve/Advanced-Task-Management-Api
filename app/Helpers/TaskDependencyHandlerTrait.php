<?php

namespace App\Helpers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

trait TaskDependencyHandlerTrait
{

    /**
     * @param Task $task
     * @return void
     */
    public function checkAndUpdateTaskDependencies(Task $task): void
    {
        Log::info("Checking dependencies for Task ID: {$task->id}");

        $dependentTasks = $task->dependents;

        foreach ($dependentTasks as $dependentTask) {

            if ($dependentTask->dependency && $dependentTask->dependency->status !== 'Completed') {

                $dependentTask->update(['status' => 'Blocked']);

                Log::info("Dependent Task ID {$dependentTask->id} status set to 'Blocked' due to Task ID {$task->id} being incomplete.");
            } else {
                Log::info("Dependent Task ID {$dependentTask->id} does not have an incomplete dependency.");
            }
        }
    }


    public function handleDependentTasksReset(Task $task, string $oldStatus, string $newStatus): void
    {
        Log::info("Handling reset for dependent tasks of Task ID: {$task->id} from '{$oldStatus}' to '{$newStatus}'.");

        if ($newStatus === 'Completed') {
            $dependents = $task->dependents;

            foreach ($dependents as $dependentTask) {
                if ($dependentTask->status === 'Blocked') {
                    $dependentTask->update(['status' => 'Open']);
                    Log::info("Dependent Task ID {$dependentTask->id} status reset to 'Open' due to Task ID {$task->id} being completed.");
                }
            }
        }
    }
}
