<?php

namespace App\Support;

/**
 * Named groups of roles reused across route middleware definitions,
 * to avoid copy-pasted 'role:...' strings drifting apart in routes/api.php.
 */
class RoleGroups
{
    /**
     * Roles allowed to manage RH agents (CRUD, exports, dossiers, delegations)
     * and view the RH dashboard on the desktop/SPA API.
     */
    public const RH_MANAGEMENT = [
        'Section ressources humaines',
        'Chef Section RH',
        'RH National',
        'RH Provincial',
        'Assistant RH',
        'Assistant ressources humaines',
        'Assistant ressource humaine',
        'SECOM',
        'Section Nouvelle Technologie',
        'Chef Section Nouvelle Technologie',
        'Chef de Section Nouvelle Technologie',
        'SEN',
        'SEP',
    ];

    /**
     * Roles allowed to view the RH dashboard from the mobile API.
     * Kept as an alias of RH_MANAGEMENT (now includes SEP, fixing a gap where
     * SEP had management access on desktop/v1 but not on mobile).
     */
    public const RH_MOBILE_DASHBOARD = self::RH_MANAGEMENT;

    /**
     * RH_MANAGEMENT plus CAF and SEL, who may only view/create/edit
     * individual agent records (not exports, dossiers, or the delegation
     * endpoint itself) — RH National must still grant CAF/SEL the
     * create_agent/edit_agent delegation before they can write anything,
     * the same mechanism already used for Assistant RH.
     */
    public const AGENT_RECORD_MANAGEMENT = [
        ...self::RH_MANAGEMENT,
        'CAF',
        'SEL',
    ];

    /**
     * The only two roles that see the global agent-performance dashboard and
     * may validate/reject a submitted evaluation, per explicit product requirement.
     */
    public const PERFORMANCE_MANAGEMENT = ['SEN', 'RH National'];

    /**
     * Build a 'role:...' middleware string from a role group.
     *
     * @param  list<string>  $roles
     */
    public static function middleware(array $roles): string
    {
        return 'role:'.implode(',', $roles);
    }
}
