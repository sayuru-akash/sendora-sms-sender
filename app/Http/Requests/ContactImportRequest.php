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
            'type' => ['required', 'in:csv,xlsx'],
            'list_id' => ['nullable', 'integer', 'exists:lists,id'],
        ];
    }
}
