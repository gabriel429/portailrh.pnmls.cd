<?php

namespace App\Services;

use App\Models\IpAllowlistEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Best-effort "DRC only, no VPN" login gate. Free-tier by design: country
 * ranges come from AFRINIC's authoritative delegation records (not a paid
 * GeoIP database), and VPN detection is a community-maintained list of known
 * VPN/datacenter ranges — it will miss unlisted/residential-proxy VPNs, but
 * needs no per-request external API call or paid subscription.
 *
 * Enforced at login only (see AuthController), not on every request: once a
 * session/token exists it stays valid regardless of network changes, so a
 * field agent mid-session isn't cut off by a flaky connection or roaming.
 */
class GeoRestrictionService
{
    private const DRC_RANGES_PATH = 'geo/drc_ranges.json';
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

        $drcRanges = $this->loadRanges(self::DRC_RANGES_PATH, 'geo:drc_ranges');
        if ($drcRanges === []) {
            // The refresh command hasn't populated the data yet (fresh
            // install, or the scheduled fetch failed) — fail open instead
            // of locking out every single user until someone notices.
            Log::warning('GeoRestrictionService: DRC ranges are empty, allowing all countries until refreshed.');

            return ['allowed' => true, 'reason' => null];
        }

        if (!$this->ipInRanges($ip, $drcRanges)) {
            return ['allowed' => false, 'reason' => 'country'];
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
        Cache::forget('geo:drc_ranges');
    }
}
