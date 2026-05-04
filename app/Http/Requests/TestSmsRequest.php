<?php

namespace App\Http\Requests;

use App\Services\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class TestSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->canSendCampaigns();
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:30',
                function ($attribute, $value, $fail) {
                    $normalizer = app(PhoneNormalizer::class);
                    if (!$normalizer->validate($normalizer->normalize($value))) {
                        $fail($normalizer->getValidationError($value) ?? 'Invalid phone number.');
                    }
                },
            ],
            'message' => ['nullable', 'string', 'max:1600'],
        ];
    }
}
