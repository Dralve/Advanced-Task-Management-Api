<?php

namespace App\Http\Requests\Dependency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddTaskDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dependent_task_id' => [
                'required',
                'exists:tasks,id',
                Rule::notIn([$this->task->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'dependent_task_id.required' => 'The dependent task ID is required.',
            'dependent_task_id.exists' => 'The selected dependent task does not exist.',
            'dependent_task_id.not_in' => 'A task cannot depend on itself.'
        ];
    }
}

