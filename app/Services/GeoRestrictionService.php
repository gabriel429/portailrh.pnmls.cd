<?php

namespace App\Services;

use App\Models\IpAllowlistEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Best-effort "no VPN" login gate — free-tier by design: VPN detection is a
 * community-maintained list of known VPN/datacenter ranges, not a paid API.
 * It will miss unlisted/residential-proxy VPNs.
 *
 * A country-only restriction (DRC) was tried and dropped: legitimate DRC
 * users routinely connect through corporate/institutional proxies whose
 * exit IP is registered abroad (e.g. a Fastly-owned US range), so a strict
 * "AFRINIC-delegated-to-CD" check produced real false positives.
 *
 * Enforced at login only (see AuthController), not on every request: once a
 * session/token exists it stays valid regardless of network changes, so a
 * field agent mid-session isn't cut off by a flaky connection or roaming.
 */
class GeoRestrictionService
{
    private const VPN_RANGES_PATH = 'geo/vpn_ranges.json';
    private const ALLOWLIST_CACHE_KEY = 'geo:allowlist';
    private const RANGES_CACHE_TTL = 3600;

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function evaluate(string $ip): array
    {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['allowed' => true, 'reason' => null];
        }

        // Private/loopback/reserved ranges: local dev, SSH tunnels, internal
        // health checks. Never a real end-user connection, so never blocked.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($this->isAllowlisted($ip)) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($this->ipInRanges($ip, $this->loadRanges(self::VPN_RANGES_PATH, 'geo:vpn_ranges'))) {
            return ['allowed' => false, 'reason' => 'vpn'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    private function isAllowlisted(string $ip): bool
    {
        $allowlist = Cache::remember(
            self::ALLOWLIST_CACHE_KEY,
            self::RANGES_CACHE_TTL,
            fn () => IpAllowlistEntry::query()->pluck('ip_address')->all()
        );

        return in_array($ip, $allowlist, true);
    }

    /**
     * @return list<string> CIDR ranges
     */
    private function loadRanges(string $path, string $cacheKey): array
    {
        return Cache::remember($cacheKey, self::RANGES_CACHE_TTL, function () use ($path) {
            if (!Storage::exists($path)) {
                return [];
            }

            $decoded = json_decode(Storage::get($path), true);

            return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
        });
    }

    /**
     * @param  list<string>  $ranges
     */
    private function ipInRanges(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($this->ipInCidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($subnetBinary, 0, $bytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainderBits)) & 0xFF);

        return (substr($ipBinary, $bytes, 1) & $mask) === (substr($subnetBinary, $bytes, 1) & $mask);
    }

    public function forgetCaches(): void
    {
        Cache::forget(self::ALLOWLIST_CACHE_KEY);
        Cache::forget('geo:vpn_ranges');
    }
}
