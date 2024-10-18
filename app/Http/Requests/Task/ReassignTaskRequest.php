<?php

namespace App\Http\Requests\Task;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class ReassignTaskRequest extends FormRequest
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
            'assigned_to' => 'required|exists:users,id'
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'The Assign Field is Required.',
            'assigned_to.exists' => 'The Selected User Does not Exists.',
        ];
    }

    public function passedValidation(): void
    {
        Log::info('Assign Validation Success');
    }

    public function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
