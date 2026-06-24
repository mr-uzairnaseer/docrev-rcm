<?php

namespace App\Support;

use App\Models\User;

class RolePermissions
{
    /** @var array<string, list<string>> */
    private const MATRIX = [
        'super_admin' => ['*'],
        'org_admin' => ['*'],
        'provider' => [
            'clinical.read', 'clinical.write', 'encounters.sign', 'prescriptions.write',
            'patients.manage', 'appointments.manage', 'labs.manage', 'hie.read', 'reports.read',
            'chart.write', 'audit.read',
        ],
        'nurse' => [
            'clinical.read', 'clinical.write', 'encounters.sign', 'patients.manage',
            'appointments.manage', 'labs.manage', 'reports.read', 'chart.write',
        ],
        'biller' => [
            'clinical.read', 'reports.read', 'audit.read',
        ],
        'front_desk' => [
            'clinical.read', 'patients.manage', 'appointments.manage', 'reports.read', 'chart.read',
        ],
    ];

    public static function allows(?User $user, string $ability): bool
    {
        if (! $user) {
            return false;
        }

        $role = $user->role ?? '';
        $grants = self::MATRIX[$role] ?? ['clinical.read'];

        if (in_array('*', $grants, true)) {
            return true;
        }

        if (in_array($ability, $grants, true)) {
            return true;
        }

        $prefix = explode('.', $ability)[0] ?? $ability;

        return in_array($prefix.'.*', $grants, true)
            || in_array($prefix.'.read', $grants, true);
    }

    /** @return list<string> */
    public static function roles(): array
    {
        return array_keys(self::MATRIX);
    }
}
