<?php

namespace App\Services\Sms;

class SmsResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?array $rawResponse = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $sent = false,
    ) {}

    public static function success(?string $providerMessageId = null, ?array $rawResponse = null): self
    {
        return new self(
            success: true,
            providerMessageId: $providerMessageId,
            rawResponse: $rawResponse,
            sent: true,
        );
    }

    public static function failure(string $errorMessage, ?array $rawResponse = null): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
            sent: false,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider_message_id' => $this->providerMessageId,
            'raw_response' => $this->rawResponse,
            'error_message' => $this->errorMessage,
            'sent' => $this->sent,
        ];
    }
}
