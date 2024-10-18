<?php

namespace App\Http\Requests\User;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateUserRequest extends FormRequest
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
            'name' => 'sometimes|string|max:50',
            'email' => 'sometimes|string|email|unique:users,email',
            'password' => 'sometimes|string|min:8',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email Address',
            'password' => 'Password',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The :attribute Field must be of Type String.',
            'name.max' => 'The :attribute Field must be at Most :max Characters',
            'email.unique' => 'The :attribute Field is Already Taken',
            'email.email' => 'The :attribute must be a Valid Email Address.',
            'password.string' => 'The :attribute Field must be of Type String.',
            'password.min' => 'The :attribute Field must be at Least :min Characters.'
        ];
    }

    protected function passedValidation(): void
    {
        Log::info('Update User Validation Success');
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
