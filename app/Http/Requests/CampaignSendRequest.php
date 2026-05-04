<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canSendCampaigns();
    }

    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
        ];
    }
}
