<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canImportContacts();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // 10MB max
            'type' => ['nullable', 'in:csv,xlsx'],
            'duplicate_handling' => ['nullable', 'in:skip,update,add,add_to_list'],
            'list_ids' => ['nullable', 'array'],
            'list_ids.*' => ['integer', 'exists:lists,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'list_id' => ['nullable', 'integer', 'exists:lists,id'],
        ];
    }
}
