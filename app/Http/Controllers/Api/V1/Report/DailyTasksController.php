<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\Task\TaskResource;
use App\Jobs\GenerateDailyTaskReport;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyTasksController extends Controller
{
    /**
     * Generate a daily task report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dailyTasks(Request $request): JsonResponse
    {
        GenerateDailyTaskReport::dispatch()->delay(now()->addMinutes(2));

        $today = Carbon::today()->toDateString();
        $tasks = Task::whereDate('created_at', $today)->get();

        // Return the response
        return response()->json([
            'status' => 'success',
            'message' => 'Daily report generation has been scheduled.',
            'data' => TaskResource::collection($tasks),
        ]);
    }
}
