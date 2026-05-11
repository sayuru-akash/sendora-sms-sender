<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['delete', 'tag', 'untag', 'add_to_list', 'remove_from_list', 'update_status'])],
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'tag_ids' => ['required_if:action,tag,untag', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'list_id' => ['required_if:action,add_to_list,remove_from_list', 'nullable', 'integer', 'exists:lists,id'],
            'status' => ['required_if:action,update_status', 'nullable', Rule::in(['active', 'inactive', 'unsubscribed', 'blocked', 'invalid'])],
        ];
    }
}
