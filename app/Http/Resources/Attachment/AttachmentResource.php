<?php

namespace App\Http\Resources\Attachment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_path' => $this->file_path,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
        ];
    }
}
