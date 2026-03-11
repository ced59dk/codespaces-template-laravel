<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default tenant for testing
        $tenant = Tenant::updateOrCreate(
            ['name' => 'Default Tenant'],
            [
                'slug' => 'default',
                'contact_email' => null,
            ]
        );

        // Create a test user with admin role
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => User::ROLE_ADMIN,
                'active' => true,
            ]
        );

        // Create another test tenant
        $tenant2 = Tenant::updateOrCreate(
            ['name' => 'Test Tenant'],
            [
                'slug' => 'test',
                'contact_email' => null,
            ]
        );

        // Create user for second tenant
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant2->id,
                'role' => User::ROLE_OPS,
                'active' => true,
            ]
        );
    }
}
