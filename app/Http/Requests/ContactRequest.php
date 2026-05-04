<?php

namespace App\Http\Requests;

use App\Services\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    public function rules(): array
    {
        $contactId = $this->route('contact')?->id;

        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:30',
                function ($attribute, $value, $fail) use ($contactId) {
                    $normalizer = app(PhoneNormalizer::class);
                    $normalized = $normalizer->normalize($value);

                    if (!$normalizer->validate($normalized)) {
                        $fail($normalizer->getValidationError($value) ?? 'Invalid phone number.');
                        return;
                    }

                    if ($normalizer->isDuplicate($normalized, $contactId)) {
                        $fail('A contact with this phone number already exists.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('contacts', 'email')->ignore($contactId)],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'unsubscribed', 'blocked', 'invalid', 'bounced'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'lists' => ['nullable', 'array'],
            'lists.*' => ['integer', 'exists:lists,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        if ($phone) {
            $normalizer = app(PhoneNormalizer::class);
            $this->merge([
                'phone_normalised' => $normalizer->normalize($phone),
            ]);
        }
    }
}
