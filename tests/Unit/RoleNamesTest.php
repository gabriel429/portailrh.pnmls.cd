<?php

namespace Tests\Unit;

use App\Support\RoleNames;
use PHPUnit\Framework\TestCase;

class RoleNamesTest extends TestCase
{
    public function test_rh_aliases_match_dashboard_roles(): void
    {
        $this->assertTrue(RoleNames::matches('Section RH', ['Section ressources humaines']));
        $this->assertTrue(RoleNames::matches('Responsable RH', ['Section ressources humaines']));
        $this->assertTrue(RoleNames::matches('Chef de Section Ressources Humaines', ['Chef Section RH']));
        $this->assertTrue(RoleNames::matches('RH Nationale', ['RH National']));
        $this->assertTrue(RoleNames::matches('Responsable RH Provinciale', ['RH Provincial']));
    }
}
