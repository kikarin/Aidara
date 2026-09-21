<?php

namespace Database\Seeders\Booking;

use App\Models\Role;
use Illuminate\Database\Seeder;

class BookingRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin_upt',
                'bg' => 'bg-info',
                'init_page_login' => 'dashboard',
                'is_allow_login' => 1,
                'is_vertical_menu' => true,
            ],
            [
                'name' => 'penyewa',
                'bg' => 'bg-success',
                'init_page_login' => 'dashboard',
                'is_allow_login' => 1,
                'is_vertical_menu' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ],
                [
                    'bg' => $role['bg'],
                    'init_page_login' => $role['init_page_login'],
                    'is_allow_login' => $role['is_allow_login'],
                    'is_vertical_menu' => $role['is_vertical_menu'],
                ]
            );
        }

        $this->command?->info('Booking roles seeded: admin_upt, penyewa');
    }
}
