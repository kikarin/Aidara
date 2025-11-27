<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Database\Seeder;

class SetRolePermissionSeeder extends Seeder
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
        $permission_wajib_ada = ['Profile Show', 'Profile Change Role', 'Dashboard Show', 'Menu Sidebar Show'];

        $permission_acl = [
            'Users Show',
            'Users Add',
            'Users Edit',
            'Users Detail',
            'Users Delete',
            'Users Login As',
            'Role Show',
            'Role Add',
            'Role Edit',
            'Role Detail',
            'Role Delete',
            'Role Set Permission',
            'Permission Show',
            'Permission Add',
            'Permission Edit',
            'Permission Detail',
            'Permission Delete',
            'Users Menu Show',
            'Users Menu Add',
            'Users Menu Edit',
            'Users Menu Detail',
            'Users Menu Delete',
            'Activity Log Show',
            'Activity Log Detail',
            'Activity Log Delete',
            'Users Management Show',

            'Setting Sidebar Show',
            'Master Show',
            'Identity Show',
            'Identity Add',
            'Identity Edit',
            'Identity Detail',
            'Identity Delete',
        ];

        $role_id_array = Role::get()->pluck('id')->toArray();

        $permission_id_all = Permission::get()->pluck('id')->toArray();

        $permission_admin = Permission::whereNotIn('name', $permission_acl)->get()->pluck('id')->toArray();

        $permission_user_api_default = Permission::whereIn('name', ['API Menu Show'])->get()->pluck('id')->toArray();

        $list_permission_pimpinan = array_merge($permission_wajib_ada, ['Laporan Sidebar Show']);
        $permission_pimpinan      = Permission::whereIn(
            'name',
            $list_permission_pimpinan
        )->get()->pluck('id')->toArray();

        foreach ($role_id_array as $key => $value) {
            if ($value == 1) {
                $this->repository->setPermission($value, $permission_admin);
            } elseif ($value == 10) { // Pimpinan
                $this->repository->setPermission($value, $permission_pimpinan);
            } elseif ($value == 11) { // Super Admin
                $this->repository->setPermission($value, $permission_id_all);
            } elseif ($value == 34) { // User API
                $this->repository->setPermission($value, $permission_user_api_default);
            }
        }
        $this->command->info('Set Role Permission table seeded!');
        //
    }
}
