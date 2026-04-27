<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['Super Admin', 'Branch Manager']);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'An employee must be selected for assignment.',
            'employee_id.exists'   => 'The selected employee account does not exist.',
        ];
    }
}
