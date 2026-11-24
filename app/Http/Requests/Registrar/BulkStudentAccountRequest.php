<?php

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStudentAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('accounts.student.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_ids' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],
            'student_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('students', 'id'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_ids.required' => 'Student IDs are required.',
            'student_ids.array' => 'Student IDs must be an array.',
            'student_ids.min' => 'At least one student ID is required.',
            'student_ids.max' => 'Maximum of 200 student IDs allowed.',
            'student_ids.*.integer' => 'Each student ID must be an integer.',
            'student_ids.*.distinct' => 'Student IDs must be unique.',
            'student_ids.*.exists' => 'One or more student IDs do not exist.',
        ];
    }
}