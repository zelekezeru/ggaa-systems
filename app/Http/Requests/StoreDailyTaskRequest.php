<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Super Admin', 'Branch Manager']);
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'type'           => ['required', 'in:mail_delivery,client_visit,tax_commission,errand,internal_meeting,other'],
            'assigned_to'    => ['required', 'integer', 'exists:users,id'],
            'branch_id'      => ['required', 'integer', 'exists:branches,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'priority'       => ['required', 'in:normal,urgent'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
