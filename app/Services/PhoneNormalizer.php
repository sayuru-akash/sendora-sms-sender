<?php

namespace App\Services;

use App\Models\Contact;

class PhoneNormalizer
{
    /**
     * Normalize a phone number to 94771234567 format.
     */
    public function normalize(string $phone): string
    {
        // Remove all spaces, dashes, brackets, and symbols
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading +
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // If starts with 0, replace leading 0 with 94
        if (str_starts_with($phone, '0')) {
            $phone = '94' . substr($phone, 1);
        }

        // If 9 digits starting with 7, prefix 94
        if (strlen($phone) === 9 && str_starts_with($phone, '7')) {
            $phone = '94' . $phone;
        }

        return $phone;
    }

    /**
     * Validate a Sri Lankan mobile phone number.
     * Must start with 947, total 12 digits.
     */
    public function validate(string $phone): bool
    {
        $normalized = $this->normalize($phone);

        return preg_match('/^947\d{8}$/', $normalized) === 1;
    }

    /**
     * Format a normalised phone number for display.
     */
    public function formatForDisplay(string $normalised): string
    {
        if (strlen($normalised) !== 11 || !str_starts_with($normalised, '94')) {
            return $normalised;
        }

        return '+94 ' . substr($normalised, 2, 2) . ' ' . substr($normalised, 4, 3) . ' ' . substr($normalised, 7, 4);
    }

    /**
     * Check if a normalised phone number already exists in the database.
     */
    public function isDuplicate(string $normalised, ?int $excludeId = null): bool
    {
        $query = Contact::where('phone_normalised', $normalised);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get a validation error message for a phone number.
     */
    public function getValidationError(string $phone): ?string
    {
        $normalized = $this->normalize($phone);

        if (empty($normalized) || strlen($normalized) < 9) {
            return 'Phone number is too short.';
        }

        if (!str_starts_with($normalized, '94')) {
            return 'Phone number must be a Sri Lankan number starting with 94.';
        }

        if (!str_starts_with($normalized, '947')) {
            return 'Phone number must be a Sri Lankan mobile number starting with 947.';
        }

        if (strlen($normalized) !== 11) {
            return 'Phone number must be exactly 11 digits (e.g., 94771234567).';
        }

        if (preg_match('/^947\d{8}$/', $normalized) !== 1) {
            return 'Invalid Sri Lankan mobile phone number format.';
        }

        return null;
    }
}
