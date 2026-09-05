<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Superadmin',
                'email' => 'superadmin@marketplace.test',
                'password' => 'password',
                'phone' => '081234567890',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Admin Toko',
                'email' => 'admin@marketplace.test',
                'password' => 'password',
                'phone' => '081298765432',
                'role' => 'admin',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'pengguna@marketplace.test',
                'password' => 'password',
                'phone' => '081355667788',
                'role' => 'pengguna',
            ],
            [
                'name' => 'Siti Rahayu',
                'email' => 'siti@example.com',
                'password' => 'password',
                'phone' => '081377889900',
                'role' => 'pengguna',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [...$user, 'email_verified_at' => now()],
            );
        }
    }
}