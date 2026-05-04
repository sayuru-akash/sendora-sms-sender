<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canManageSettings();
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['nullable', 'in:string,integer,boolean,json,text'],
            'settings.*.group' => ['nullable', 'string', 'max:255'],
        ];
    }
}
