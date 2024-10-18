<?php

namespace App\Http\Requests\Task;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class AddCommentRequest extends FormRequest
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
            'content' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'The Content Field is Required.',
            'content.string' => 'The Content Field must be of Type String.'
        ];
    }

    public function passedValidation(): void
    {
        Log::info('Comment Validation Success');
    }

    public function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
