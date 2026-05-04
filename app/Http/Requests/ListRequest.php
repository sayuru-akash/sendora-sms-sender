<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    public function rules(): array
    {
        $listId = $this->route('list')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('lists', 'name')->ignore($listId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'colour' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ];
    }
}
