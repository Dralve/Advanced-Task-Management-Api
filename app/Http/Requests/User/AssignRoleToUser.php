<?php

namespace App\Http\Requests\User;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class AssignRoleToUser extends FormRequest
{
    use  ApiResponseTrait;
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
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Roles are required.',
            'roles.array' => 'Roles must be an array.',
            'roles.*.exists' => 'The selected role is invalid.',
        ];
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        Log::info('Assign Validation success');
    }

    /**
     * @param Validator $validator
     * @return mixed
     */
    protected function failedValidation(Validator $validator): mixed
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
