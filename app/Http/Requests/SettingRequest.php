<?php

namespace App\Http\Requests;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canManageSettings();
    }

    public function rules(): array
    {
        return [
            'settings' => ['nullable', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255', Rule::in(SystemSetting::editableKeys())],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['nullable', 'in:string,integer,boolean,json,text'],
            'settings.*.group' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'date_format' => ['nullable', 'string', 'max:255'],
            'default_country_code' => ['nullable', 'string', 'max:10'],
            'max_import_file_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'default_duplicate_handling' => ['nullable', Rule::in(['skip', 'update', 'add_to_list'])],
        ];
    }
}
