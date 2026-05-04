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
            'message_body' => ['required', 'string', 'max:1600'],
            'sender_id' => ['nullable', 'string', 'max:11'],
            'target_type' => ['required', Rule::in(['all_contacts', 'list', 'tag', 'saved_segment', 'manual_selection', 'advanced_filter'])],
            'target_filters' => ['nullable', 'array'],
            'target_filters.list_ids' => ['required_if:target_type,list', 'nullable', 'array'],
            'target_filters.list_ids.*' => ['integer', 'exists:lists,id'],
            'target_filters.tag_ids' => ['required_if:target_type,tag', 'nullable', 'array'],
            'target_filters.tag_ids.*' => ['integer', 'exists:tags,id'],
            'target_filters.segment_id' => ['required_if:target_type,saved_segment', 'nullable', 'integer', 'exists:saved_segments,id'],
            'target_filters.contact_ids' => ['required_if:target_type,manual_selection', 'nullable', 'array'],
            'target_filters.contact_ids.*' => ['integer', 'exists:contacts,id'],
            'target_filters.advanced' => ['required_if:target_type,advanced_filter', 'nullable', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled'])],
        ];
    }
}
