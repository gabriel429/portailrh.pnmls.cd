<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rafikisauveur@pnmls.cd'],
            [
                'name'           => 'Super Admin',
                'email'          => 'rafikisauveur@pnmls.cd',
                'password'       => Hash::make(env('SUPERADMIN_PASSWORD', 'Raf!k1$auveur2026#')),
                'is_super_admin' => true,
                'agent_id'       => null,
                'role_id'        => null,
            ]
        );
    }
}
