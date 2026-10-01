<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@sp-accounting.com',
        ]);

        $admin->roles()->syncWithoutDetaching(
            \App\Models\Role::firstOrCreate(['name' => \App\Models\Role::ADMIN], ['description' => 'Can manage users and roles'])
        );
    }
}
