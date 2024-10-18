<?php

namespace App\Http\Requests\Task;

use App\Helpers\ApiResponseTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class AddAttachment extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('add-attachment');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file_path' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240',
            'suffix' => 'required|string|in:docs'
        ];
    }

    public function attributes(): array
    {
        return [
            'file_path' => 'File',
            'suffix' => 'Suffix',
        ];
    }

    public function messages(): array
    {
        return [
            'file_path.required' => 'The :attribute is Required.',
            'file_path.file' => 'The :attribute Field must be a Valid File.',
            'file_path.mimes' => 'The :attribute must be of the following type: pdf, doc, docx, xls, xlsx, ppt, or pptx.',
            'file_path.max' => 'The :attribute size must not exceed 10MB.',
            'suffix.required' => 'The :attribute is Required.',
            'suffix.in' => 'The specified suffix is invalid, It must be docs.',
        ];
    }

    public function passedValidation(): void
    {
        Log::info('Attachment Validation Success');
    }

    public function failedValidation(Validator $validator)
    {
        $error = $validator->errors()->getMessages();
        throw new HttpResponseException($this->errorResponse($error, 'Validation Failed', 422));
    }
}
