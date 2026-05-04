<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1600'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
