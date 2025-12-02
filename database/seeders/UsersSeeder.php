<?php

namespace Database\Seeders;

use App\Models\User;
use App\Repositories\UsersRepository;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    protected $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listData = [];

        $listData = array_merge($listData, [
            [
                'name'                 => 'Imran',
                'email'                => 'superadmin@gmail.com',
                'email_verified_at'    => null,
                'tanggal_lahir'        => null,
                'no_hp'                => '08512312311',
                'password'             => 'Sewdaq123',
                'is_active'            => 1,
                'remember_token'       => null,
                'verification_token'   => null,
                'reset_password_token' => null,
                'deskripsi'            => null,
                'is_verifikasi'        => 1,
                'role_id'              => [11],
            ],
            [
                'name'                 => 'Handoko', // Pimpinan
                'email'                => 'handoko@gmail.com',
                'email_verified_at'    => null,
                'tanggal_lahir'        => null,
                'no_hp'                => '0814123123',
                'password'             => 'Sewdaq123',
                'is_active'            => 1,
                'remember_token'       => null,
                'verification_token'   => null,
                'reset_password_token' => null,
                'deskripsi'            => null,
                'is_verifikasi'        => 1,
                'role_id'              => [1],
            ],
            [
                'name'                 => 'Fajar', // User
                'email'                => 'fajarmuhamad997@gmail.com',
                'email_verified_at'    => null,
                'tanggal_lahir'        => null,
                'no_hp'                => '0852812930123',
                'password'             => 'Sewdaq123',
                'is_active'            => 1,
                'remember_token'       => null,
                'verification_token'   => null,
                'reset_password_token' => null,
                'deskripsi'            => null,
                'is_verifikasi'        => 1,
                'role_id'              => [2],
            ],
            [
                'name'                 => 'Front Page',
                'email'                => 'frontpage@gmail.com',
                'email_verified_at'    => null,
                'tanggal_lahir'        => null,
                'no_hp'                => '085123123',
                'password'             => 'Frontpage123',
                'is_active'            => 1,
                'remember_token'       => null,
                'verification_token'   => null,
                'reset_password_token' => null,
                'deskripsi'            => null,
                'is_verifikasi'        => 1,
                'role_id'              => [34],
            ],
        ]);

        foreach ($listData as $data) {
            $record = $this->repository->create($data);
        }
        $this->command->info('Users table seeded!');
    }
}
