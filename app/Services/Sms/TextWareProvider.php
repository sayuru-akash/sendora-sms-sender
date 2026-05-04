<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextWareProvider implements SmsProviderInterface
{
    public function getName(): string
    {
        return 'textware';
    }

    public function send(string $phone, string $message, ?string $senderId = null): SmsResult
    {
        $username = config('sms.username');
        $password = config('sms.password');
        $source = $senderId ?? config('sms.source');
        $apiUrl = config('sms.api_url');
        $timeout = config('sms.timeout_seconds', 30);

        // Validate credentials
        if (empty($username) || empty($password)) {
            Log::error('SMS provider credentials not configured', [
                'provider' => $this->getName(),
                'has_username' => !empty($username),
                'has_password' => !empty($password),
            ]);

            return SmsResult::failure('SMS provider credentials are not configured.');
        }

        if (empty($apiUrl)) {
            Log::error('SMS API URL not configured', [
                'provider' => $this->getName(),
            ]);

            return SmsResult::failure('SMS API URL is not configured.');
        }

        try {
            $response = Http::timeout($timeout)
                ->asForm()
                ->post($apiUrl, [
                    'username' => $username,
                    'password' => $password,
                    'source' => $source ?? '',
                    'destination' => $phone,
                    'message' => $message,
                ]);

            $body = $response->body();
            $statusCode = $response->status();

            // Parse response - TextWare typically returns status in the body
            $rawResponse = [
                'status_code' => $statusCode,
                'body' => $body,
            ];

            if ($response->successful()) {
                // Try to extract a message ID from the response
                $providerMessageId = null;
                if ($response->header('Content-Type') && str_contains($response->header('Content-Type'), 'json')) {
                    $json = $response->json();
                    $rawResponse['json'] = $json;
                    $providerMessageId = $json['message_id'] ?? $json['id'] ?? null;
                }

                Log::info('SMS sent successfully', [
                    'provider' => $this->getName(),
                    'phone' => $phone,
                    'provider_message_id' => $providerMessageId,
                    'status_code' => $statusCode,
                ]);

                return SmsResult::success(
                    providerMessageId: $providerMessageId,
                    rawResponse: $rawResponse,
                );
            }

            Log::warning('SMS provider returned error', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'status_code' => $statusCode,
                'body' => $body,
            ]);

            return SmsResult::failure(
                errorMessage: "Provider returned status {$statusCode}: {$body}",
                rawResponse: $rawResponse,
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SMS provider connection failed', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Network error: Unable to connect to SMS provider. ' . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('SMS provider request failed', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Request error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('SMS provider unexpected error', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Unexpected error: ' . $e->getMessage());
        }
    }
}
