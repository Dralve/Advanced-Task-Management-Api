<?php

namespace App\Http\Requests\User;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class ChangePassword extends FormRequest
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
            'old_password' => 'required|string|min:8',
            'password' => 'required|string|min:8|confirmed'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'old_password.required' => 'The Old Password Field is Required.',
            'old_password.string' => 'The Old Password Field must be of Type String.',
            'old_password.min' => 'The Old Password must be at Least :min Characters.',
            'password.required' => 'The Password Field is Required.',
            'password.string' => 'The Password Field must be of Type String.',
            'password.min' => 'The Password Field must be at Least :min Characters.',
            'Password.confirmed' => 'The Password Confirmation does not Match.'
        ];
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        Log::info('Change Password Validation Success');
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
