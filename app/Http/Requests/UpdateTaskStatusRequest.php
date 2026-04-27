<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the employee this task is assigned to may drag it between columns.
        return $this->route('task')->assigned_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:Waiting on Client,To Do,In Review,Done'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status value supplied.',
        ];
    }
}
