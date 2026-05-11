<?php

namespace App\Services\Sms;

use App\Models\Contact;

class MessagePersonalizer
{
    /**
     * @return list<string>
     */
    public function unresolvedPlaceholders(string $message): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $message, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function render(string $message, Contact $contact): string
    {
        $values = $this->contactValues($contact);

        return preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            fn (array $matches): string => array_key_exists($matches[1], $values)
                ? $values[$matches[1]]
                : $matches[0],
            $message
        ) ?? $message;
    }

    /**
     * @return array<string, string>
     */
    private function contactValues(Contact $contact): array
    {
        return [
            'first_name' => trim((string) $contact->first_name),
            'last_name' => trim((string) $contact->last_name),
            'full_name' => trim((string) ($contact->full_name ?: $contact->display_name)),
            'name' => trim((string) ($contact->full_name ?: $contact->display_name)),
            'phone' => trim((string) $contact->phone),
            'phone_normalised' => trim((string) $contact->phone_normalised),
            'email' => trim((string) $contact->email),
            'company' => trim((string) $contact->company),
            'job_title' => trim((string) $contact->job_title),
            'country' => trim((string) $contact->country),
            'district' => trim((string) $contact->district),
            'city' => trim((string) $contact->city),
            'gender' => trim((string) $contact->gender),
            'source' => trim((string) $contact->source),
        ];
    }
}
