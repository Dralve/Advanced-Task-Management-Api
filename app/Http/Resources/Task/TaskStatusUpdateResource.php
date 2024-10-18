<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskStatusUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'task_id' => $this->task_id,
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'updated_by' => $this->updated_by,
        ];
    }
}
