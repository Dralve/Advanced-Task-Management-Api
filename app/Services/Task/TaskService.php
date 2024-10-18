<?php

namespace App\Services\Task;

use App\Helpers\ApiResponseTrait;
use App\Helpers\FileStorageTrait;
use App\Helpers\TaskDependencyHandlerTrait;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\TaskStatusUpdate;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{

    use ApiResponseTrait;
    use FileStorageTrait;
    use TaskDependencyHandlerTrait;

    /**
     * @param $perPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listTasks($perPage, array $filters): LengthAwarePaginator
    {
        try {
            $user = Auth::user();
            $cacheKey = 'tasks_' . $user->id . '_' . md5(json_encode($filters));

            $tasks = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($perPage, $filters, $user) {
                $query = Task::query();

                if ($user->hasRole('manager')) {
                    $query->where('created_by', $user->id);
                }

                if (isset($filters['status'])) {
                    $query->status($filters['status']);
                }

                if (isset($filters['priority'])) {
                    $query->priority($filters['priority']);
                }

                if (isset($filters['type'])) {
                    $query->type($filters['type']);
                }

                if (isset($filters['assigned_to'])) {
                    $query->assignedTo($filters['assigned_to']);
                }

                if (isset($filters['due_date'])) {
                    $query->dueDate($filters['due_date']);
                }

                if (isset($filters['depends_on'])) {
                    $query->dependsOn($filters['depends_on']);
                }

                return $query->paginate($perPage);
            });

            return $tasks;
        } catch (Exception $e) {
            Log::error('Error Listing Tasks: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param $perPage
     * @return mixed
     */
    public function getMyTasks($perPage): mixed
    {
        try {
            $userId = Auth::user()->id;

            $cacheKey = 'user_tasks_' . $userId;

            $tasks = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($userId, $perPage) {
                return Task::where('assigned_to', $userId)->paginate($perPage);
            });

            return $tasks;
        } catch (Exception $e) {
            Log::error('Error Retrieving User Tasks: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'There is something wrong in the server', 500));
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function createTask(array $data): mixed
    {
        try {
            $user = Auth::user();

            if (!$user->can('create-tasks')) {
                Log::warning('Unauthorized task creation attempt by user ID: ' . $user->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to create a task.', 403));
            }

            $data['created_by'] = $user->id;

            $task = Task::create($data);

            Cache::forget('user_tasks_' . $user->id);

            Cache::forget('all_tasks');

            return $task;
        } catch (HttpResponseException $e) {
            Log::error('Error Creating Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Creating Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server', 500));
        }
    }

    /**
     * @param Task $task
     * @return Task
     * @throws Exception
     */
    public function showTask(Task $task): Task
    {
        try {
            $user = Auth::user();
            $cacheKey = 'task_' . $task->id;

            $cachedTask = Cache::get($cacheKey);
            if ($cachedTask) {
                return $cachedTask;
            }

            if ($user->hasRole('admin')) {
                $taskData = $task->load('assignedTo', 'createdBy');
            } elseif ($user->hasRole('manager')) {
                if ($task->created_by === $user->id) {
                    $taskData = $task->load('assignedTo', 'createdBy');
                } else {
                    throw new HttpResponseException($this->errorResponse(null, 'Unauthorized action', 403));
                }
            } else {
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to perform this action.', 403));
            }

            Cache::put($cacheKey, $taskData, now()->addMinutes(10));

            return $taskData;

        } catch (ModelNotFoundException $e) {
            Log::error('Task Not Found: ' . $e->getMessage(), ['task_id' => $task->id, 'user_id' => $user->id]);
            throw new Exception('Task Not Found');
        } catch (HttpResponseException $e) {
            Log::error('HTTP Response Exception: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Retrieving Task: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'exception' => $e,
            ]);
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong with Server', 500));
        }
    }

    /**
     * @param Task $task
     * @param array $data
     * @return Task
     */
    public function updateTask(Task $task, array $data): Task
    {
        try {
            $user = Auth::user();

            if (!$user->can('update-tasks')) {
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to perform this action.', 403));
            }

            if ($user->id !== $task->created_by && !$user->hasRole('admin')) {
                Log::warning('Unauthorized task update attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You cannot update tasks you did not create.', 403));
            }

            $oldStatus = $task->status;
            $task->update($data);

            $this->checkAndUpdateTaskDependencies($task);

            if ($oldStatus !== $task->status) {
                $this->handleDependentTasksReset($task, $oldStatus, $task->status);
            }

            return $task;

        } catch (HttpResponseException $e) {
            Log::error('Error Updating Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Updating Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server: ' . $e->getMessage(), 500));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Task $task
     * @return void
     */
    public function deleteTask(Task $task): void
    {
        try {
            $user = Auth::user();

            if (!$user->can('delete-tasks')) {
                Log::warning('Unauthorized delete attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to perform this action.', 403));
            }

            if ($user->hasRole('manager') && $task->created_by !== $user->id) {
                Log::warning('Manager user ID: ' . $user->id . ' attempted to delete a task they did not create. Task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized action', 403));
            }

            $cacheKey = 'task_' . $task->id;
            Cache::forget($cacheKey);

            $task->delete();

            Log::info('Task ID: ' . $task->id . ' successfully deleted by user ID: ' . $user->id);

        } catch (HttpResponseException $e) {
            Log::error('Error Deleting Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Deleting Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server: ' . $e->getMessage(), 500));
        }
    }

    /**
     * @param $id
     * @return array|Model|Collection|Builder|null
     * @throws Exception
     */
    public function restoreTask($id): array|Model|Collection|Builder|null
    {
        try {
            $user = Auth::user();

            if (!$user->can('restore-tasks')) {
                Log::warning('Unauthorized restore attempt by user ID: ' . $user->id . ' on task ID: ' . $id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to perform this action.', 403));
            }

            $task = Task::onlyTrashed()->findOrFail($id);

            if ($user->hasRole('manager') && $task->created_by !== $user->id) {
                Log::warning('Manager user ID: ' . $user->id . ' attempted to restore a task they did not create. Task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'Unauthorized action', 403));
            }

            $task->restore();

            $cacheKey = 'task_' . $task->id;
            Cache::put($cacheKey, $task, now()->addHours(4));

            Log::info('Task ID: ' . $task->id . ' restored successfully by user ID: ' . $user->id);

            return $task;

        } catch (ModelNotFoundException $e) {
            Log::error('Task Not Found for Restoration: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Task Not Found', 404));

        } catch (HttpResponseException $e) {
            Log::error('Error Restoring Task: ' . $e->getMessage());
            throw $e;

        } catch (Exception $e) {
            Log::error('Error Restoring Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server', 500));
        }
    }

    /**
     * @param $perPage
     * @return LengthAwarePaginator
     */
    public function trashedTasks($perPage): LengthAwarePaginator
    {
        try {
            $user = Auth::user();

            if (!$user->can('view-trashed-tasks')) {
                Log::warning('Unauthorized trashed tasks view attempt by user ID: ' . $user->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to view trashed tasks.', 403));
            }

            $cacheKey = $user->hasRole('manager') ? 'trashed_tasks_manager_' . $user->id : 'trashed_tasks_all';

            if (Cache::has($cacheKey)) {
                $trashedTasks = Cache::get($cacheKey);
                Log::info('Retrieved trashed tasks from cache for user ID: ' . $user->id);
            } else {
                if ($user->hasRole('manager')) {
                    $trashedTasks = Task::onlyTrashed()->where('created_by', $user->id)->paginate($perPage);
                } else {
                    $trashedTasks = Task::onlyTrashed()->paginate($perPage);
                }

                Cache::put($cacheKey, $trashedTasks, now()->addMinutes(30));
                Log::info('Cached trashed tasks for user ID: ' . $user->id);
            }

            return $trashedTasks;

        } catch (Exception $e) {
            Log::error('Error Listing Trashed Tasks: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Wrong With Server', 500));
        }
    }

    /**
     * @param $id
     * @return void
     */
    public function forceDeleteTask($id): void
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('admin')) {
                Log::warning('Unauthorized force delete attempt by user ID: ' . $user->id . ' on task ID: ' . $id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to force delete a task.', 403));
            }

            $task = Task::withTrashed()->findOrFail($id);

            $task->forceDelete();

        } catch (ModelNotFoundException $e) {
            Log::error('Task Not Found for Force Deletion: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Task Not Found', 404));

        } catch (HttpResponseException $e) {
            Log::error('Error Force Deleting Task (HttpResponseException): ' . $e->getMessage());
            throw $e;

        } catch (Exception $e) {
            Log::error('Error Force Deleting Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server', 500));
        }
    }

    /**
     * @param Task $task
     * @param string $newStatus
     * @return TaskStatusUpdate
     */
    public function updateStatus(Task $task, string $newStatus): TaskStatusUpdate
    {
        try {
            $user = auth()->user();
            $oldStatus = $task->status;

            if ($user->hasRole('developer')) {
                if ($task->assigned_to !== $user->id) {
                    Log::warning('Developer user ID: ' . $user->id . ' attempted to update a task they are not assigned to. Task ID: ' . $task->id);
                    throw new HttpResponseException($this->errorResponse(null, 'Unauthorized action', 403));
                }
            } elseif (!$user->can('update-status-tasks')) {
                Log::warning('Unauthorized status update attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to update the task status.', 403));
            }

            $task->update(['status' => $newStatus]);

            Log::info("Task ID {$task->id} status updated from '{$oldStatus}' to '{$newStatus}' by user ID {$user->id}");

            $taskStatusUpdate = TaskStatusUpdate::create([
                'task_id' => $task->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => $user->id,
            ]);

            return $taskStatusUpdate;

        } catch (HttpResponseException $e) {
            Log::error('Error Updating Status : ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Changing Status: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong with the server', 500));
        }
    }

    /**
     * @param Task $task
     * @param array $data
     * @return Task
     */
    public function assignTask(Task $task, array $data): Task
    {
        try {
            $user = auth()->user();

            if (!$user->can('assign-task')) {
                Log::warning('Unauthorized task assignment attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to assign tasks.', 403));
            }

            if ($user->hasRole('manager') && $task->created_by !== $user->id) {
                Log::warning('Manager user ID: ' . $user->id . ' attempted to assign a task they did not create. Task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You can only assign tasks you created.', 403));
            }

            $assignedUser = User::find($data['assigned_to']);
            if ($assignedUser && $assignedUser->hasRole('admin')) {
                Log::warning('Attempt to assign task to admin user ID: ' . $assignedUser->id . ' by user ID: ' . $user->id);
                throw new HttpResponseException($this->errorResponse(null, 'You cannot assign tasks to admin users.', 403));
            }

            $task->update(['assigned_to' => $data['assigned_to']]);

            $cacheKey = 'task_assignment_' . $task->id;

            Cache::forget($cacheKey);

            Log::info("Task ID {$task->id} successfully assigned to user ID {$data['assigned_to']} by user ID {$user->id}");

            return $task->load('assignedTo');

        } catch (HttpResponseException $e) {
            Log::error('Error Assigning Task To User : ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Assigning Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something Went Wrong With Server', 500));
        }
    }
    /**
     * @param Task $task
     * @param array $data
     * @return Task
     */
    public function reassignTask(Task $task, array $data): Task
    {
        try {
            $user = auth()->user();

            if (!$user->can('assign-task')) {
                Log::warning('Unauthorized task re-assignment attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to assign tasks.', 403));
            }

            if ($user->hasRole('manager')) {
                if ($task->created_by !== $user->id) {
                    Log::warning('Manager user ID: ' . $user->id . ' attempted to reassign a task they did not create. Task ID: ' . $task->id);
                    throw new HttpResponseException($this->errorResponse(null, 'You can only reassign tasks you created.', 403));
                }
            }

            $assignedUser = User::find($data['assigned_to']);
            if ($assignedUser && $assignedUser->hasRole('admin')) {
                Log::warning('Attempt to reassign task to admin user ID: ' . $assignedUser->id . ' by user ID: ' . $user->id);
                throw new HttpResponseException($this->errorResponse(null, 'You cannot assign tasks to admin users.', 403));
            }

            $task->update(['assigned_to' => $data['assigned_to']]);
            return $task->load('assignedTo');

        } catch (HttpResponseException $e) {
            Log::error('Error Reassigning Task To User: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Reassigning Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong with the server', 500));
        }
    }

    /**
     * @param Task $task
     * @param array $data
     * @param int $userId
     * @return Model
     */
    public function addComments(Task $task, array $data, int $userId): Model
    {
        try {
            $user = User::find($userId);

            if ($user->hasRole('admin')) {
                Log::info('Admin user ID: ' . $user->id . ' is adding a comment to task ID: ' . $task->id);
            }
            elseif ($user->hasRole('manager')) {
                if ($task->created_by !== $user->id) {
                    Log::warning('Unauthorized comment attempt by manager user ID: ' . $user->id . ' on task ID: ' . $task->id);
                    throw new HttpResponseException($this->errorResponse(null, 'You can only comment on tasks you created.', 403));
                }
            }
            elseif ($user->hasRole('developer')) {
                if ($task->assigned_to !== $user->id) {
                    Log::warning('Unauthorized comment attempt by developer user ID: ' . $user->id . ' on task ID: ' . $task->id);
                    throw new HttpResponseException($this->errorResponse(null, 'You can only comment on tasks assigned to you.', 403));
                }
            } else {
                Log::warning('Unauthorized comment attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to add comments.', 403));
            }

            $comment = $task->comments()->create([
                'content' => $data['content'],
                'user_id' => $userId,
            ]);

            $cacheKey = 'task_comments_' . $task->id;
            Cache::forget($cacheKey);

            Log::info("Comment added to task ID {$task->id} by user ID {$user->id}");

            return $comment->load('user');

        } catch (HttpResponseException $e) {
            Log::error('Error Adding Comment to Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Adding Comment to Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong with the server', 500));
        }
    }

    /**
     * Delete a comment by its ID.
     *
     * @param Task $task
     * @param int $commentId
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function deleteComment(Task $task, int $commentId, int $userId): void
    {
        try {
            $comment = $task->comments()->findOrFail($commentId);

            $user = User::findOrFail($userId);

            if ($user->hasRole('admin')) {
                Log::info('Admin user ID: ' . $user->id . ' deleted comment ID: ' . $comment->id . ' from task ID: ' . $task->id);
            }
            elseif ($comment->user_id !== $userId) {
                Log::warning('Unauthorized delete attempt by user ID: ' . $user->id . ' on comment ID: ' . $comment->id);
                throw new HttpResponseException($this->errorResponse(null, 'You are only allowed to delete your own comments.', 403));
            }

            $comment->delete();
            Log::info('Comment ID: ' . $comment->id . ' deleted successfully by user ID: ' . $user->id);

        } catch (ModelNotFoundException $e) {
            Log::error('Comment not found: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Comment not found', 404));

        } catch (HttpResponseException $e) {
            Log::error('Error Deleting Comment from Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Deleting Comment from Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong with the server', 500));
        }
    }

    /**
     * Add a dependency to a task. This makes the task dependent on another task.
     *
     * @param Task $task The task that depends on another task.
     * @param Task $dependentTask The task that the main task is dependent on.
     * @return Task The updated task.
     * @throws Exception
     */
    public function addTaskDependency(Task $task, Task $dependentTask): Task
    {
        try {
            TaskDependency::create([
                'task_id' => $task->id,
                'depends_on' => $dependentTask->id,
            ]);

            if ($dependentTask->status !== 'Completed') {
                $task->status = 'Blocked';
                $task->save();
            }

            return $task;
        } catch (Exception $e) {
            throw new Exception("Error adding task dependency: " . $e->getMessage());
        }
    }

    /**
     * @param Task $task
     * @return Task
     * @throws Exception
     */
    public function checkAndUpdateBlockedStatus(Task $task): Task
    {
        try {
            $dependentTask = $task->dependsOnTask;

            if ($dependentTask && $dependentTask->status !== 'Completed') {
                $task->status = 'Blocked';
            } else {
                $task->status = 'Open';
            }

            $task->save();
            return $task;
        } catch (\Exception $e) {
            throw new \Exception("Error checking and updating task status: " . $e->getMessage());
        }
    }


    /**
     * @param Task $task
     * @param array $data
     * @return Attachment
     * @throws Exception
     */
    public function addAttachment(Task $task, array $data): Attachment
    {
        try {
            $user = auth()->user();

            if (!$user->can('add-attachment')) {
                Log::warning('Unauthorized attachment attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to add attachments.', 403));
            }

            if ($user->hasRole('manager') && $task->created_by !== $user->id) {
                Log::warning('Manager user ID: ' . $user->id . ' attempted to add attachment to a task they did not create. Task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You can only add attachments to tasks you created.', 403));
            }

            return DB::transaction(function () use ($task, $data, $user) {
                $file = $data['file_path'];
                $suffix = $data['suffix'];
                $folderName = 'attachments';

                $fileUrl = $this->storeFile($file, $folderName, $suffix);

                $attachment = new Attachment([
                    'file_path' => $fileUrl,
                    'user_id' => $user->id,
                ]);

                $task->attachments()->save($attachment);

                $cacheKey = 'task_attachments_' . $task->id;
                Cache::forget($cacheKey);

                Log::info("Attachment added to task ID {$task->id} by user ID {$user->id}");

                return $attachment;
            });

        } catch (HttpResponseException $e) {
            Log::error('Error Adding Attachment to Task: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Adding Attachment to Task: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong with the server', 500));
        }
    }

    /**
     * @param Task $task
     * @param int $attachmentId
     * @return void
     */
    public function deleteAttachment(Task $task, int $attachmentId): void
    {
        try {
            $user = auth()->user();

            $attachment = Attachment::findOrFail($attachmentId);

            if (!$user->can('delete-attachment')) {
                Log::warning('Unauthorized attachment deletion attempt by user ID: ' . $user->id . ' on task ID: ' . $task->id);
                throw new HttpResponseException($this->errorResponse(null, 'You do not have permission to delete attachments.', 403));
            }

            if ($user->hasRole('admin')) {
                $this->deleteFile($attachment->file_path);
                $attachment->delete();
                Log::info('Admin user ID: ' . $user->id . ' deleted attachment ID: ' . $attachmentId);
                return;
            }

            if ($attachment->user_id !== $user->id) {
                Log::warning('Unauthorized deletion attempt by user ID: ' . $user->id . ' on attachment ID: ' . $attachmentId);
                throw new HttpResponseException($this->errorResponse(null, 'You can only delete attachments you have created.', 403));
            }

            $this->deleteFile($attachment->file_path);
            $attachment->delete();
            Log::info('User ID: ' . $user->id . ' deleted their attachment ID: ' . $attachmentId);

        } catch (ModelNotFoundException $e) {
            Log::error('Attachment Not Found: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Attachment not found', 404));
        } catch (HttpResponseException $e) {
            Log::error('Error Deleting Attachment: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error Deleting Attachment: ' . $e->getMessage());
            throw new HttpResponseException($this->errorResponse(null, 'Something went wrong', 500));
        }
    }
}
