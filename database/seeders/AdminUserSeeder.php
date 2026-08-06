<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@pharmaplus.com'],
            [
                'f_name' => 'Admin',
                'l_name' => 'System',
                'age' => 30,
                'gender' => 'male',
                'phone_number' => '01000000000',
                'location' => 'Damascus, Syria',
                'password' => bcrypt('password'),
            ],
        );

        $admin->assignRole('admin');
    }
}
