<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name'              => 'Administrator',
                'password'          => Hash::make('@Gonzales101'),
                'email_verified_at' => now(),
            ]
        );
    }
}
