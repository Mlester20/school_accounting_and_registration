<?php

namespace App\Http\Requests\Registrar;

use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('students.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $departmentIds = $this->user()->departmentIds();

        return [
            'student_id' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{5}$/',
                Rule::unique('students', 'student_id')->whereNull('deleted_at'),
                Rule::unique('users', 'user_id'),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', Rule::in(Sex::cases())],
            'contact_number' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->whereIn('department_id', $departmentIds)],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->request->remove('user_fk_id');
        $this->request->remove('status');
        $this->request->remove('user_id');
    }
}