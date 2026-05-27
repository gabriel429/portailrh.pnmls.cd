<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_metadata_endpoint_is_available(): void
    {
        $this->getJson('/api')->assertOk();
    }

    public function test_pwa_manifest_endpoint_is_available(): void
    {
        $this->get('/pwa-manifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8')
            ->assertJsonPath('name', 'E-PNMLS');
    }

    public function test_legacy_manifest_url_is_available(): void
    {
        $this->get('/manifest.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=utf-8')
            ->assertJsonPath('name', 'E-PNMLS');
    }
}
