<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['name' => 'faculty'],
            [
                'email' => 'faculty@ispas.local',
                'password' => Hash::make('faculty123'),
                'role' => 'faculty',
            ]
        );
    }
}
