<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canCreateCampaigns();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'message_body' => ['required', 'string', 'max:'.config('sms.max_message_characters', 10000)],
            'sender_id' => ['nullable', 'string', 'max:11'],
            'target_type' => ['required', Rule::in(['all_contacts', 'list', 'tag', 'saved_segment', 'manual_selection', 'advanced_filter'])],
            'target_filters' => ['nullable', 'array'],
            'target_filters.list_ids' => ['exclude_unless:target_type,list', 'required', 'array', 'min:1'],
            'target_filters.list_ids.*' => ['integer', 'exists:lists,id'],
            'target_filters.tag_ids' => ['exclude_unless:target_type,tag', 'required', 'array', 'min:1'],
            'target_filters.tag_ids.*' => ['integer', 'exists:tags,id'],
            'target_filters.segment_id' => ['required_if:target_type,saved_segment', 'nullable', 'integer', 'exists:saved_segments,id'],
            'target_filters.contact_ids' => ['exclude_unless:target_type,manual_selection', 'required', 'array', 'min:1'],
            'target_filters.contact_ids.*' => ['integer', 'exists:contacts,id'],
            'target_filters.advanced' => ['required_if:target_type,advanced_filter', 'nullable', 'array'],
            'template_id' => ['nullable', 'integer', 'exists:sms_templates,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled'])],
            'send_now' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('target_filters')) {
            return;
        }

        $this->merge([
            'target_filters' => [
                'list_ids' => $this->input('list_ids', []),
                'tag_ids' => $this->input('tag_ids', []),
                'contact_ids' => $this->input('contact_ids', []),
            ],
        ]);
    }
}
