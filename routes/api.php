<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Report\DailyTasksController;
use App\Http\Controllers\Api\V1\Task\TaskController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth/v1')->group(function (){
   Route::post('login', [AuthController::class, 'login']);
   Route::post('refresh', [AuthController::class, 'refresh']);
   Route::post('logout', [AuthController::class, 'logout']);
   Route::get('current', [AuthController::class, 'current']);
});

Route::middleware('security')->group(function (){

    Route::middleware('auth:api')->group(function (){

        Route::prefix('v1')->group(function (){

            Route::apiResource('users', UserController::class);
            Route::post('users/{id}/restore', [UserController::class, 'restore']);
            Route::get('trashed/users', [UserController::class, 'trashedUsers']);
            Route::delete('users/{id}/force-delete', [UserController::class, 'forceDelete']);
            Route::post('user/{user}/assign-role', [UserController::class, 'assignRoleToUser']);
            Route::post('user/{user}/unassign-role', [UserController::class, 'unassignRoleToUser']);
            Route::post('user/{user}/change-password', [UserController::class, 'changePassword']);

//  --------------------------------------------------------------------------------------------------------------------

            Route::apiResource('tasks', TaskController::class);

            Route::put('tasks/{task}/assign', [TaskController::class, 'assignTask']);
            Route::put('tasks/{task}/reassign', [TaskController::class, 'reassignTask']);

            Route::post('/tasks/{task}/dependencies', [TaskController::class, 'addDependency']);

            Route::put('tasks/{task}/updated-status', [TaskController::class, 'changeStatusTasks']);

            Route::post('tasks/{task}/comment', [TaskController::class, 'addComments']);
            Route::delete('tasks/{task}/comments/{commentId}', [TaskController::class, 'deleteComment']);

            Route::post('tasks/{task}/attachments', [TaskController::class, 'addAttachment']);
            Route::delete('tasks/{task}/attachments-delete/{attachmentId}', [TaskController::class, 'deleteAttachment']);

            Route::get('my-tasks', [TaskController::class, 'getMyTasks']);
            Route::get('/reports/daily-tasks', [DailyTasksController::class, 'dailyTasks']);

            Route::post('tasks/{id}/restore', [TaskController::class, 'restore']);
            Route::get('trashed-tasks', [TaskController::class, 'trashedTasks']);
            Route::delete('tasks/{id}/force-delete', [TaskController::class, 'forceDelete']);

        });
    });
});


