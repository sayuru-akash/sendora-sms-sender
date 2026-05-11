<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                'has_username' => ! empty($username),
                'has_password' => ! empty($password),
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
                ->get($apiUrl, [
                    'username' => $username,
                    'password' => $password,
                    'src' => $source ?? '',
                    'dst' => $phone,
                    'msg' => $message,
                    'dr' => '1',
                ]);

            $body = $this->sanitizeProviderBody($response->body());
            $statusCode = $response->status();

            // Parse response - TextWare typically returns status in the body
            $rawResponse = [
                'status_code' => $statusCode,
                'body' => $body,
            ];

            if ($response->successful() && $this->isSuccessfulProviderBody($body, $response->header('Content-Type'), $rawResponse)) {
                $providerMessageId = $this->extractProviderMessageId($body, $rawResponse['json'] ?? null);

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
                errorMessage: "Provider returned status {$statusCode}: ".$this->summarizeProviderError($body),
                rawResponse: $rawResponse,
            );
        } catch (ConnectionException $e) {
            Log::error('SMS provider connection failed', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Network error: Unable to connect to SMS provider. '.$e->getMessage());
        } catch (RequestException $e) {
            Log::error('SMS provider request failed', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Request error: '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error('SMS provider unexpected error', [
                'provider' => $this->getName(),
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsResult::failure('Unexpected error: '.$e->getMessage());
        }
    }

    private function sanitizeProviderBody(string $body): string
    {
        $sanitized = $body;

        foreach ([config('sms.username'), config('sms.password')] as $sensitiveValue) {
            if (is_string($sensitiveValue) && $sensitiveValue !== '') {
                $sanitized = str_replace($sensitiveValue, '[redacted]', $sanitized);
                $sanitized = str_replace(rawurlencode($sensitiveValue), '[redacted]', $sanitized);
                $sanitized = str_replace(urlencode($sensitiveValue), '[redacted]', $sanitized);
            }
        }

        $sanitized = preg_replace('/([?&](?:username|password)=)[^&"\'<>\s]+/i', '$1[redacted]', $sanitized) ?? $sanitized;

        return Str::limit($sanitized, 1000);
    }

    private function summarizeProviderError(string $body): string
    {
        $summary = trim(html_entity_decode(strip_tags($body)));
        $summary = preg_replace('/\s+/', ' ', $summary) ?? $summary;

        return $summary === '' ? 'No response body.' : Str::limit($summary, 240);
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    private function isSuccessfulProviderBody(string $body, ?string $contentType, array &$rawResponse): bool
    {
        $summary = mb_strtolower(trim(strip_tags($body)));

        if ($summary === '') {
            return false;
        }

        if (preg_match('/\b(invalid|fail(?:ed|ure)?|error|denied|unauthori[sz]ed|rejected|insufficient)\b/i', $summary)) {
            return false;
        }

        if ($contentType && str_contains($contentType, 'json')) {
            $json = json_decode($body, true);
            $rawResponse['json'] = $json;

            if (! is_array($json)) {
                return false;
            }

            $status = mb_strtolower((string) ($json['status'] ?? $json['state'] ?? $json['result'] ?? ''));

            return ($json['success'] ?? null) === true
                || in_array($status, ['success', 'sent', 'ok', 'queued', 'accepted'], true)
                || isset($json['message_id'])
                || isset($json['id']);
        }

        return preg_match('/\b(operation\s+success|success|sent|queued|accepted|ok)\b/i', $summary) === 1;
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractProviderMessageId(string $body, ?array $json): ?string
    {
        if ($json) {
            $id = $json['message_id'] ?? $json['id'] ?? null;

            return $id ? (string) $id : null;
        }

        if (preg_match('/Operation success:\s*(\S+)/i', $body, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
