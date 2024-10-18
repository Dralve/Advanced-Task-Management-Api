<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Helpers\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dependency\AddTaskDependencyRequest;
use App\Http\Requests\Task\AddAttachment;
use App\Http\Requests\Task\AddCommentRequest;
use App\Http\Requests\Task\AssignRequest;
use App\Http\Requests\Task\ReassignTaskRequest;
use App\Http\Requests\Task\TaskRequest;
use App\Http\Requests\Task\UpdateStatusRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\Attachment\AttachmentResource;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Task\TaskResource;
use App\Http\Resources\Task\TaskStatusUpdateResource;
use App\Models\Task;
use App\Services\Task\TaskService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    use ApiResponseTrait;

    protected TaskService $taskService;
    public function __construct(TaskService $taskService,)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $per_Page = $request->input('per_page');
        $filters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'type' => $request->query('type'),
            'assigned_to' => $request->query('assigned_to'),
            'due_date' => $request->query('due_date'),
            'depends_on' => $request->query('depends_on') === 'null' ? null : $request->query('depends_on'),
        ];
        $tasks = $this->taskService->listTasks($per_Page, $filters);
        return $this->resourcePaginated(TaskResource::collection($tasks));
    }

    /**
     * Retrieve all tasks for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyTasks(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page');
        $task = $this->taskService->getMyTasks($perPage);
        return $this->resourcePaginated(TaskResource::collection($task));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TaskRequest $request
     * @return JsonResponse
     */
    public function store(TaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->createTask($data);
        return $this->successResponse(new TaskResource($task), 'Task Created Successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Task $task
     * @return JsonResponse
     * @throws Exception
     */
    public function show(Task $task): JsonResponse
    {
        $task = $this->taskService->showTask($task);
        return $this->successResponse(new TaskResource($task));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->updateTask($task, $data);
        return $this->successResponse(new TaskResource($task), 'Task Updated Successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Task $task
     * @return JsonResponse
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task);
        return $this->successResponse(null, 'Task Deleted Successfully', 200);
    }

    /**
     * Assign task to a specific user.
     * @param AssignRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function assignTask(AssignRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->assignTask($task, $data);
        return $this->successResponse(new TaskResource($task), 'Task Assigned Successfully', 200);
    }

    /**
     * Reassign task to a specific user.
     *
     * @param ReassignTaskRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function reassignTask(ReassignTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->reassignTask($task, $data);
        return $this->successResponse(new TaskResource($task), 'Task Reassigned Successfully', 200);
    }

    /**
     * Change the status of a specific task.
     *
     * @param UpdateStatusRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function changeStatusTasks(UpdateStatusRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->updateStatus($task, $data['new_status']);
        return $this->successResponse(new TaskStatusUpdateResource($task), 'Status Updated Successfully', 200);
    }

    /**
     * Add comments to a specific task.
     *
     * @param AddCommentRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function addComments(AddCommentRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $userId = Auth::id();
        $comment = $this->taskService->addComments($task, $data, $userId);

        return $this->successResponse(new CommentResource($comment), 'Comment Added Successfully', 200);

    }

    /**
     * @param Task $task
     * @param int $commentId
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteComment(Task $task, int $commentId): JsonResponse
    {
        $userId = Auth::id();
        $this->taskService->deleteComment($task, $commentId, $userId);
        return $this->successResponse(null, 'Comment Deleted Successfully', 200);
    }

    /**
     * @param AddTaskDependencyRequest $request
     * @param Task $task
     * @return JsonResponse
     * @throws Exception
     */
    public function addDependency(AddTaskDependencyRequest $request, Task $task): JsonResponse
    {
        $dependentTaskId = $request->input('dependent_task_id');
        $dependentTask = Task::findOrFail($dependentTaskId);

        $task = $this->taskService->addTaskDependency($task, $dependentTask);

        return $this->successResponse(new TaskResource($task), 'Dependency added successfully', 200);
    }

    /**
     * Add attachments to a specific task.
     *
     * @param AddAttachment $request
     * @param Task $task
     * @return JsonResponse
     * @throws Exception
     */
    public function addAttachment(AddAttachment $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $attachment = $this->taskService->addAttachment($task,$data);
        return $this->successResponse(new AttachmentResource($attachment), 'Attachment Added Successfully', 200);
    }

    /**
     * Delete a specific attachment from storage.
     *
     * @param $taskId
     * @param $attachmentId
     * @return JsonResponse
     */
    public function deleteAttachment($taskId, $attachmentId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        $this->taskService->deleteAttachment($task, $attachmentId);
        return response()->json(['message' => 'Attachment deleted successfully'], 200);
    }

    /**
     * Restore a task by its ID.
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function restore($id): JsonResponse
    {
        $task = $this->taskService->restoreTask($id);
        return response()->json(['message' => 'Task Restored Successfully', $task], 200);
    }

    /**
     * Display a paginated listing of the trashed resources.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trashedTasks(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page');
        $task = $this->taskService->trashedTasks($perPage);
        return $this->resourcePaginated(TaskResource::collection($task));
    }

    /**
     * Permanently delete a specified resource by its ID.
     *
     * @param $id
     * @return JsonResponse
     */
    public function forceDelete($id): JsonResponse
    {
        $this->taskService->forceDeleteTask($id);
        return $this->successResponse(null, 'Task Deleted Successfully', 200);
    }
}
