<?php

namespace App\Console\Commands;

use App\Services\GeoRestrictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Refreshes the two free data sources behind the login-time "DRC only, no
 * VPN" gate:
 *  - AFRINIC's delegated-extended stats file (authoritative RIR allocation
 *    records) for which IPv4/IPv6 ranges are assigned to the DRC (CD).
 *  - X4BNet/lists_vpn's community-maintained list of known VPN/datacenter
 *    ranges (best-effort — will miss unlisted VPNs, but needs no paid API).
 */
class RefreshGeoRestrictionRanges extends Command
{
    protected $signature = 'geo:refresh-ranges';

    protected $description = 'Télécharge les plages IP RDC (AFRINIC) et VPN connus utilisées par la restriction de connexion';

    private const AFRINIC_STATS_URL = 'https://ftp.afrinic.net/stats/afrinic/delegated-afrinic-extended-latest';
    private const VPN_IPV4_URL = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/ipv4.txt';
    private const VPN_IPV6_URL = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/ipv6.txt';

    public function handle(GeoRestrictionService $geo): int
    {
        $drcOk = $this->refreshDrcRanges();
        $vpnOk = $this->refreshVpnRanges();

        $geo->forgetCaches();

        return ($drcOk && $vpnOk) ? self::SUCCESS : self::FAILURE;
    }

    private function refreshDrcRanges(): bool
    {
        $response = Http::timeout(60)->get(self::AFRINIC_STATS_URL);

        if (!$response->successful()) {
            $this->error('Échec du téléchargement AFRINIC: HTTP ' . $response->status());

            return false;
        }

        $ranges = [];

        foreach (explode("\n", $response->body()) as $line) {
            $fields = explode('|', trim($line));

            if (count($fields) < 7 || $fields[0] !== 'afrinic' || $fields[1] !== 'CD') {
                continue;
            }

            $type = $fields[2];
            $startIp = $fields[3];
            $value = $fields[4];

            if ($type === 'ipv4') {
                // Field 5 is a host count for ipv4 records, always a power
                // of two for allocated blocks — convert to a prefix length.
                $count = (int) $value;
                if ($count < 1 || !filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    continue;
                }
                $prefix = 32 - (int) round(log($count, 2));
                $ranges[] = "{$startIp}/{$prefix}";
            } elseif ($type === 'ipv6') {
                // Field 5 is already the prefix length for ipv6 records.
                if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    continue;
                }
                $ranges[] = "{$startIp}/{$value}";
            }
        }

        if ($ranges === []) {
            $this->error('Aucune plage RDC trouvée dans le fichier AFRINIC — fichier inattendu, non enregistré.');

            return false;
        }

        Storage::put('geo/drc_ranges.json', json_encode(array_values(array_unique($ranges))));
        $this->info(count($ranges) . ' plages RDC enregistrées.');

        return true;
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
            $this->error('Aucune plage VPN récupérée — listes non enregistrées.');

            return false;
        }

        Storage::put('geo/vpn_ranges.json', json_encode(array_values(array_unique($ranges))));
        $this->info(count($ranges) . ' plages VPN/datacenter enregistrées.');

        return true;
    }
}
