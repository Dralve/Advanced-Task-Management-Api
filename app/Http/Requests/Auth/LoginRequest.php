<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class LoginRequest extends FormRequest
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
            'email' => 'required|email|string|max:255',
            'password' => 'required|string|min:8'
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'Email Address',
            'password' => 'Password',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The :attribute Field is Required.',
            'email.email' => 'The :attribute Field must be Valid :attribute',
            'email.max' => 'The :attribute Field may not be greater than :max characters.',
            'password.required' => 'The :attribute Field is Required.',
            'password.string' => 'The :attribute Field must be of Type String.',
            'password.min' => 'The :attribute Field must be at least :min Characters.'
        ];
    }

    protected function passedValidation(): void
    {
        Log::info('Login Validation successfully');
    }

    protected function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Error', 422));
    }
}
