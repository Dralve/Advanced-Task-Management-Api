<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "updated" event.
     *
     * @param Task $task
     * @return void
     */
    public function updated(Task $task): void
    {
        // If the task is completed, check its blocking tasks and update their status.
        if ($task->status === 'Completed') {
            foreach ($task->blockingTasks as $dependency) {
                $dependentTask = $dependency->task;
                $dependentTask->updateStatusBasedOnDependencies();
            }
        }

        // If a task's status is not completed, ensure its dependent tasks are blocked.
        if ($task->status !== 'Completed') {
            foreach ($task->blockingTasks as $dependency) {
                $dependentTask = $dependency->task;
                if ($dependentTask->isBlocked()) {
                    $dependentTask->updateStatusBasedOnDependencies();
                }
            }
        }
    }
}
