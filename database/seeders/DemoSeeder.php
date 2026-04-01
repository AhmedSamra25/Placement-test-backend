<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed a demo organization with one Admin and one Moderator user.
     *
     * Credentials:
     *   Admin:     admin@school.edu  / password: "password"
     *   Moderator: alice@school.edu  / password: "password"
     */
    public function run(): void
    {
        // 1. Create the demo organization
        $org = Organization::firstOrCreate(
            ['name' => 'Springfield Language School'],
            [
                'license_limit' => 100,
                'license_used'  => 0,
            ]
        );

        // 2. Admin user
        User::updateOrCreate(
            ['email' => 'admin@school.edu'],
            [
                'org_id'   => $org->id,
                'name'     => 'School Admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );

        // 3. Moderator user
        User::updateOrCreate(
            ['email' => 'alice@school.edu'],
            [
                'org_id'   => $org->id,
                'name'     => 'Alice Johnson',
                'password' => Hash::make('password'),
                'role'     => 'moderator',
                'status'   => 'active',
            ]
        );

        $this->command->info('✅ DemoSeeder complete.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',     'admin@school.edu', 'password'],
                ['Moderator', 'alice@school.edu', 'password'],
            ]
        );
    }
}
