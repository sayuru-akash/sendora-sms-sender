<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canImportContacts();
    }

    public function rules(): array
    {
        return [
            'column_mapping' => ['required', 'array'],
            'column_mapping.phone' => ['required', 'string'],
            'column_mapping.first_name' => ['nullable', 'string'],
            'column_mapping.last_name' => ['nullable', 'string'],
            'column_mapping.full_name' => ['nullable', 'string'],
            'column_mapping.email' => ['nullable', 'string'],
            'column_mapping.company' => ['nullable', 'string'],
            'column_mapping.job_title' => ['nullable', 'string'],
            'column_mapping.district' => ['nullable', 'string'],
            'column_mapping.city' => ['nullable', 'string'],
            'column_mapping.gender' => ['nullable', 'string'],
            'column_mapping.date_of_birth' => ['nullable', 'string'],
            'column_mapping.source' => ['nullable', 'string'],
            'column_mapping.notes' => ['nullable', 'string'],
            'phone_column' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.duplicate_handling' => ['nullable', 'in:skip,update,add_to_list'],
            'options.default_status' => ['nullable', 'in:active,inactive'],
            'options.default_source' => ['nullable', 'string', 'max:255'],
            'list_id' => ['nullable', 'integer', 'exists:lists,id'],
        ];
    }
}
