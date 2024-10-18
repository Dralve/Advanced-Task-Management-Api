<?php

namespace App\Http\Requests\Task;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateStatusRequest extends FormRequest
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
            'new_status' => [
                'required',
                'in:Open,In_Progress,Completed,Blocked'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'new_status.required' => 'The new status is required.',
            'new_status.in' => 'The status must be one of the following: Open, In_Progress, Completed, or Blocked.',
        ];
    }

    public function passedValidation(): void
    {
        Log::info('Update Status Validation Success');
    }

    public function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
