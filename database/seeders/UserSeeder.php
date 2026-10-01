<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@serbisyong-congpleyto.com',
            'password' => bcrypt('password'), // Change to a secure password in production
            'email_verified_at' => now()
        ]);

        $admin->roles()->syncWithoutDetaching(
            \App\Models\Role::firstOrCreate(['name' => \App\Models\Role::ADMIN], ['description' => 'Can manage users and roles'])
        );
    }
}
