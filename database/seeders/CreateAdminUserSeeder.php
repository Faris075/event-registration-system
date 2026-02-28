<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->update(['is_admin' => false]);

        User::updateOrCreate(
            ['email' => 'Farisnabil075@gmail.com'],
            [
                'name' => 'Faris Nabil',
                'password' => Hash::make('Admin@1234'),
                'is_admin' => true,
            ]
        );
    }
}
