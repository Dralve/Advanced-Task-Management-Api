<?php

namespace App\Http\Requests\Task;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateTaskRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:Bug,Feature,Improvement',
            'status' => 'sometimes|in:Open,In_Progress,Completed,Blocked',
            'priority' => 'sometimes|in:Low,Medium,High',
            'due_date' => 'sometimes|date_format:d-m-Y H:i|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type',
            'status' => 'Status',
            'priority' => 'Priority',
            'due_date' => 'DueDate',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'The :attribute Field must be of Type String.',
            'title.max' => 'The :attribute may not be Greater Than :max Characters.',
            'description.string' => 'The :attribute must be of Type String.',
            'type.in' => 'The :attribute must be one of The Following: Bug, Feature, Improvement.',
            'status.in' => 'The :attribute must be one of The Following: Open, In_Progress, Completed, Blocked.',
            'priority.in' => 'The :attribute must be one of The Following: Low, Medium, High.',
            'due_date.date' => 'The :attribute must be a Valid Date.',
            'due_date.after_or_equal' => 'The :attribute must be Today or a Future Date.',
            'assigned_to.exists' => 'The Selected User Does not Exists.',
        ];
    }

    protected function passedValidation(): void
    {
        Log::info('Update Task Validation Success');
    }

    protected function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
