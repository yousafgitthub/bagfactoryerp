<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $users = [
            [
                'name' => 'System Owner',
                'email' => 'owner@bagfactory.local',
                'role' => 'owner-admin',
            ],
            [
                'name' => 'Store Keeper',
                'email' => 'storekeeper@bagfactory.local',
                'role' => 'storekeeper',
            ],
            [
                'name' => 'Production Operator',
                'email' => 'operator@bagfactory.local',
                'role' => 'production-operator',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}