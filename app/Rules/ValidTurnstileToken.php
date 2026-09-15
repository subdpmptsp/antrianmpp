<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ValidTurnstileToken implements ValidationRule
{
    public function __construct(private readonly ?string $remoteIp = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::asForm()
                ->connectTimeout(2)
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('turnstile.turnstile_secret_key'),
                    'response' => (string) $value,
                    'remoteip' => $this->remoteIp,
                    'idempotency_key' => (string) Str::uuid(),
                ]);

            $result = $response->json();
            $expectedHostname = trim((string) config('turnstile.expected_hostname'));
            $hostnameMatches = $expectedHostname === ''
                || hash_equals(mb_strtolower($expectedHostname), mb_strtolower((string) ($result['hostname'] ?? '')));
            $actionMatches = hash_equals(
                (string) config('turnstile.booking_action'),
                (string) ($result['action'] ?? ''),
            );

            if (! $response->successful() || ! ($result['success'] ?? false) || ! $hostnameMatches || ! $actionMatches) {
                $fail((string) config('turnstile.error_messages.turnstile_check_message'));
            }
        } catch (Throwable $exception) {
            Log::warning('Verifikasi Turnstile antrean online gagal.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $fail('Layanan verifikasi keamanan sedang terganggu. Silakan coba kembali beberapa saat lagi.');
        }
    }
}
