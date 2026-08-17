<?php

namespace App\Http\Middleware;

use App\Models\DeviceRegistration;
use App\Services\TvZoneResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDeviceToken
{
    public function __construct(private readonly TvZoneResolver $zoneResolver)
    {
    }

    public function handle(Request $request, Closure $next, string $device, ?string $zoneNumber = null): Response
    {
        if (!config('devices.auth_enabled')) {
            return $next($request);
        }

        return match ($device) {
            'kiosk' => $this->authorizeKiosk($request, $next),
            'tv' => $this->authorizeTv($request, $next, (int) $zoneNumber),
            'tv-zone-api' => $this->authorizeTvZoneApi($request, $next),
            default => abort(500, 'Unknown device authorization type.'),
        };
    }

    private function authorizeKiosk(Request $request, Closure $next): Response
    {
        $expectedToken = $this->configuredToken(config('devices.kiosk.token'));
        $sessionHash = $request->session()->get('device_auth.kiosk_token_hash');

        if (is_string($sessionHash) && hash_equals(hash('sha256', $expectedToken), $sessionHash)) {
            $this->recordAuthorizedDevice($request, 'kiosk', 'kiosk');

            return $next($request);
        }

        $this->verifySuppliedToken($request, $expectedToken);
        $request->session()->put('device_auth.kiosk_token_hash', hash('sha256', $expectedToken));
        $this->recordAuthorizedDevice($request, 'kiosk', 'kiosk');

        return $this->continueWithoutTokenInUrl($request, $next);
    }

    private function authorizeTv(Request $request, Closure $next, int $zoneNumber): Response
    {
        $expectedToken = $this->tvToken($zoneNumber);
        $authorizedZone = (int) $request->session()->get('device_auth.tv_zone', 0);
        $sessionHash = $request->session()->get('device_auth.tv_token_hash');

        if ($authorizedZone === $zoneNumber
            && is_string($sessionHash)
            && hash_equals(hash('sha256', $expectedToken), $sessionHash)) {
            $this->recordAuthorizedDevice($request, "tv:{$zoneNumber}", 'tv', $zoneNumber);

            return $next($request);
        }

        $this->verifySuppliedToken($request, $expectedToken);
        $this->rememberTvAuthorization($request, $zoneNumber, $expectedToken);
        $this->recordAuthorizedDevice($request, "tv:{$zoneNumber}", 'tv', $zoneNumber);

        return $this->continueWithoutTokenInUrl($request, $next);
    }

    private function authorizeTvZoneApi(Request $request, Closure $next): Response
    {
        $zoneNumber = (int) $request->session()->get('device_auth.tv_zone', 0);
        $sessionHash = $request->session()->get('device_auth.tv_token_hash');

        if ($zoneNumber > 0) {
            $expectedToken = $this->tvToken($zoneNumber);

            if (!is_string($sessionHash) || !hash_equals(hash('sha256', $expectedToken), $sessionHash)) {
                $zoneNumber = 0;
            }
        }

        if ($zoneNumber === 0) {
            $suppliedToken = $this->suppliedToken($request);

            foreach (array_keys(config('tv.zones', [])) as $candidateZone) {
                $expectedToken = $this->tvToken((int) $candidateZone);

                if ($suppliedToken !== '' && hash_equals($expectedToken, $suppliedToken)) {
                    $zoneNumber = (int) $candidateZone;
                    $this->rememberTvAuthorization($request, $zoneNumber, $expectedToken);
                    break;
                }
            }
        }

        if ($zoneNumber === 0) {
            abort(403, 'Perangkat TV tidak terotorisasi.');
        }

        $counter = $this->zoneResolver->resolve($zoneNumber);
        $requestedZoneId = (int) $request->route('zoneId');

        if (!$counter || $counter->getKey() !== $requestedZoneId) {
            abort(403, 'Perangkat TV tidak diizinkan mengakses zona ini.');
        }

        $this->recordAuthorizedDevice($request, "tv:{$zoneNumber}", 'tv', $zoneNumber);

        return $next($request);
    }

    private function verifySuppliedToken(Request $request, string $expectedToken): void
    {
        $suppliedToken = $this->suppliedToken($request);

        if ($suppliedToken === '' || !hash_equals($expectedToken, $suppliedToken)) {
            abort(403, 'Perangkat tidak terotorisasi.');
        }
    }

    private function suppliedToken(Request $request): string
    {
        return (string) ($request->header('X-Device-Token') ?: $request->query('device_token', ''));
    }

    private function tvToken(int $zoneNumber): string
    {
        if (!array_key_exists($zoneNumber, config('devices.tv.tokens', []))) {
            abort(404, 'Zona TV tidak tersedia.');
        }

        return $this->configuredToken(config("devices.tv.tokens.{$zoneNumber}"));
    }

    private function configuredToken(mixed $token): string
    {
        $token = is_string($token) ? trim($token) : '';

        if ($token === '') {
            abort(503, 'Token perangkat belum dikonfigurasi.');
        }

        return $token;
    }

    private function rememberTvAuthorization(Request $request, int $zoneNumber, string $token): void
    {
        $request->session()->put([
            'device_auth.tv_zone' => $zoneNumber,
            'device_auth.tv_token_hash' => hash('sha256', $token),
        ]);
    }

    private function continueWithoutTokenInUrl(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && ! $request->expectsJson()
            && $request->query->has('device_token')) {
            return redirect()->to($request->fullUrlWithoutQuery('device_token'));
        }

        return $next($request);
    }

    private function recordAuthorizedDevice(
        Request $request,
        string $deviceKey,
        string $deviceType,
        ?int $zoneNumber = null,
    ): void {
        $sessionKey = "device_auth.inventory_last_seen.{$deviceKey}";
        $lastRecordedAt = (int) $request->session()->get($sessionKey, 0);

        if ($lastRecordedAt >= now()->subMinute()->getTimestamp()) {
            return;
        }

        DeviceRegistration::query()->updateOrCreate(
            ['device_key' => $deviceKey],
            [
                'device_type' => $deviceType,
                'zone_number' => $zoneNumber,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ],
        );
        $request->session()->put($sessionKey, now()->getTimestamp());
    }
}
