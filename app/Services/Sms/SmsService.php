<?php

namespace App\Services\Sms;

use App\Services\PhoneNormalizer;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        protected SmsProviderInterface $provider,
        protected PhoneNormalizer $phoneNormalizer,
    ) {}

    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message, ?string $senderId = null): SmsResult
    {
        // Normalize phone number
        $normalisedPhone = $this->phoneNormalizer->normalize($phone);

        // Validate phone
        if (!$this->phoneNormalizer->validate($normalisedPhone)) {
            $error = $this->phoneNormalizer->getValidationError($phone);
            Log::warning('SMS send attempted with invalid phone', [
                'phone' => $phone,
                'normalised' => $normalisedPhone,
                'error' => $error,
            ]);

            return SmsResult::failure($error ?? 'Invalid phone number.');
        }

        // Log the attempt (without sensitive data)
        Log::info('Sending SMS', [
            'provider' => $this->provider->getName(),
            'phone' => $normalisedPhone,
            'message_length' => mb_strlen($message),
            'sender_id' => $senderId,
        ]);

        // Send via provider
        $result = $this->provider->send($normalisedPhone, $message, $senderId);

        // Log the result safely
        if ($result->success) {
            Log::info('SMS sent successfully', [
                'provider' => $this->provider->getName(),
                'phone' => $normalisedPhone,
                'provider_message_id' => $result->providerMessageId,
            ]);
        } else {
            Log::warning('SMS send failed', [
                'provider' => $this->provider->getName(),
                'phone' => $normalisedPhone,
                'error' => $result->errorMessage,
            ]);
        }

        return $result;
    }

    /**
     * Get the underlying provider.
     */
    public function getProvider(): SmsProviderInterface
    {
        return $this->provider;
    }
}
