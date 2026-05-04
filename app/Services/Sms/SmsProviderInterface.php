<?php

namespace App\Services\Sms;

interface SmsProviderInterface
{
    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message, ?string $senderId = null): SmsResult;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
