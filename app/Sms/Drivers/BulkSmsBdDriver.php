<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsDriver;
use App\Sms\SmsResult;
use Illuminate\Support\Facades\Http;

/**
 * bulksmsbd.net one-to-one API.
 *
 * The endpoint is configurable rather than hard-coded: gateways in this market
 * change host names and parameter casing between accounts, so a mismatch should
 * be a settings change, not a code change. Verify the values against the API
 * document on your own account before going live.
 */
class BulkSmsBdDriver implements SmsDriver
{
    /** bulksmsbd returns 202 for "submitted successfully". */
    private const SUCCESS_CODE = 202;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly string $endpoint,
        private readonly int $timeout = 15,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        if ($this->apiKey === '' || $this->senderId === '') {
            return SmsResult::failure('API key and sender ID are required.');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->endpoint, [
                    'api_key' => $this->apiKey,
                    'senderid' => $this->senderId,
                    'number' => $to,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            return SmsResult::failure('Could not reach the SMS gateway: '.$e->getMessage());
        }

        if ($response->failed()) {
            return SmsResult::failure(
                "Gateway returned HTTP {$response->status()}.",
                ['body' => $response->body()]
            );
        }

        $body = $response->json() ?? [];
        $code = (int) ($body['response_code'] ?? 0);

        if ($code === self::SUCCESS_CODE) {
            return SmsResult::success((string) ($body['message_id'] ?? ''), $body);
        }

        // Gateways put the reason in different keys depending on the failure.
        $reason = $body['error_message']
            ?? $body['success_message']
            ?? $body['message']
            ?? $response->body();

        return SmsResult::failure("Gateway rejected the message ({$code}): {$reason}", $body);
    }

    public function name(): string
    {
        return 'bulksmsbd';
    }
}
