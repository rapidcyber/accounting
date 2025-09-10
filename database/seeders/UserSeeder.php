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
        \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@serbisyong-congpleyto.com',
            'password' => bcrypt('password'), // Change to a secure password in production
            'email_verified_at' => now()
        ]);
    }
}
