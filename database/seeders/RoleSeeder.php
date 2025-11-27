<?php

namespace Database\Seeders;

use App\Repositories\RoleRepository;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    protected $repository;

    public function __construct(RoleRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listData = [
            [
                'id'               => 1,
                'name'             => 'Admin',
                'bg'               => 'bg-info',
                'init_page_login'  => 'dashboard',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ],
            [
                'id'               => 2,
                'name'             => 'User',
                'bg'               => 'bg-dark',
                'init_page_login'  => 'dashboard',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ],
            [
                'id'               => 10,
                'name'             => 'Pimpinan',
                'bg'               => 'bg-info',
                'init_page_login'  => 'dashboard',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ], [
                'id'               => 11,
                'name'             => 'Super Admin',
                'bg'               => 'bg-danger',
                'init_page_login'  => 'dashboard',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ], [
                'id'               => 34,
                'name'             => 'User API',
                'bg'               => 'bg-secondary',
                'init_page_login'  => 'dashboard',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ], [
                'id'               => 35,
                'name'             => 'Atlet',
                'bg'               => 'bg-danger',
                'init_page_login'  => 'program-latihan',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ], [
                'id'               => 36,
                'name'             => 'Pelatih',
                'bg'               => 'bg-warning',
                'init_page_login'  => 'program-latihan',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ], [
                'id'               => 37,
                'name'             => 'Tenaga Pendukung',
                'bg'               => 'bg-success',
                'init_page_login'  => 'program-latihan',
                'is_allow_login'   => 1,
                'is_vertical_menu' => true,
            ],
        ];
        foreach ($listData as $data) {
            $this->repository->create($data);
        }
        $this->command->info('Role table seeded!');
    }
}
