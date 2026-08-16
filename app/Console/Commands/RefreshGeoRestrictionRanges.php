<?php

namespace App\Console\Commands;

use App\Services\GeoRestrictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Refreshes the free data source behind the login-time no-VPN gate:
 * X4BNet/lists_vpn's community-maintained list of known VPN/datacenter
 * ranges (best-effort — will miss unlisted VPNs, but needs no paid API).
 *
 * A country-only (DRC) check was tried and dropped — see GeoRestrictionService.
 */
class RefreshGeoRestrictionRanges extends Command
{
    protected $signature = 'geo:refresh-ranges';

    protected $description = 'Télécharge la liste des plages IP VPN/datacenter connues utilisée par la restriction de connexion';

    private const VPN_IPV4_URL = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/ipv4.txt';
    private const VPN_IPV6_URL = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/ipv6.txt';

    public function handle(GeoRestrictionService $geo): int
    {
        $ok = $this->refreshVpnRanges();

        $geo->forgetCaches();

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function refreshVpnRanges(): bool
    {
        $ranges = [];

        foreach ([self::VPN_IPV4_URL, self::VPN_IPV6_URL] as $url) {
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                $this->error("Échec du téléchargement de la liste VPN ({$url}): HTTP " . $response->status());

                continue;
            }

            foreach (explode("\n", $response->body()) as $line) {
                $line = trim($line);
                if ($line !== '' && str_contains($line, '/')) {
                    $ranges[] = $line;
                }
            }
        }

        if ($ranges === []) {
            $this->error('Aucune plage VPN récupérée — liste non enregistrée.');

            return false;
        }

        Storage::put('geo/vpn_ranges.json', json_encode(array_values(array_unique($ranges))));
        $this->info(count($ranges) . ' plages VPN/datacenter enregistrées.');

        return true;
    }
}
