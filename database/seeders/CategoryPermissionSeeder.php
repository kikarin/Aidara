<?php

namespace Database\Seeders;

use App\Models\CategoryPermission;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Permission::truncate();
        CategoryPermission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $categoryPermissions = [
            [
                'name'       => 'Dashboard',
                'permission' => [ 'Dashboard Show' ],
            ],
            [
                'name'       => 'Users',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Users Menu',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Role',
                'permission'        => 'CRUD',
                'permission_common' => ['Role Set Permission'],
            ],
            [
                'name'       => 'Permission',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Category Permission',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Activity Log',
                'permission' => ['Activity Log Show', 'Activity Log Detail', 'Activity Log Delete'],
            ],
            [
                'name'       => 'Dashboard',
                'permission' => ['Dashboard Show'],
            ],
            [
                'name'              => 'Atlet',
                'permission'        => 'CRUD',
                'permission_common' => ['Atlet Import'],
            ],
            [
                'name'       => 'Atlet Orang Tua',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Atlet Sertifikat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Atlet Prestasi',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Atlet Dokumen',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Atlet Kesehatan',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Pelatih',
                'permission'        => 'CRUD',
                'permission_common' => ['Pelatih Import'],
            ],
            [
                'name'       => 'Pelatih Sertifikat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pelatih Prestasi',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pelatih Dokumen',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pelatih Kesehatan',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Tenaga Pendukung',
                'permission'        => 'CRUD',
                'permission_common' => ['Tenaga Pendukung Import'],
            ],
            [
                'name'       => 'Tenaga Pendukung Sertifikat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Tenaga Pendukung Prestasi',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Tenaga Pendukung Kesehatan',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Tenaga Pendukung Dokumen',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Unit Pendukung',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Tingkat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Kategori Atlet',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Kategori Peserta',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Kategori Prestasi Pelatih',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Registration Approval',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Jenis Dokumen',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Kecamatan',
                'permission' => ['Mst Kecamatan Show', 'Mst Kecamatan Detail'],
            ],
            [
                'name'       => 'Mst Desa',
                'permission' => ['Mst Desa Show', 'Mst Desa Detail'],
            ],
            [
                'name'       => 'Mst Posisi Atlet',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Jenis Pelatih',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Jenis Unit Pendukung',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Jenis Tenaga Pendukung',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Juara',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Event',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Mst Parameter',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Cabor',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Cabor Kategori',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Cabor Kategori Atlet',
                'permission' => ['Cabor Kategori Atlet Show', 'Cabor Kategori Atlet Add', 'Cabor Kategori Atlet Edit', 'Cabor Kategori Atlet Delete' ],
            ],
            [
                'name'       => 'Cabor Kategori Pelatih',
                'permission' => ['Cabor Kategori Pelatih Show', 'Cabor Kategori Pelatih Add', 'Cabor Kategori Pelatih Edit', 'Cabor Kategori Pelatih Delete'],
            ],
            [
                'name'       => 'Cabor Kategori Tenaga Pendukung',
                'permission' => ['Cabor Kategori Tenaga Pendukung Show', 'Cabor Kategori Tenaga Pendukung Add', 'Cabor Kategori Tenaga Pendukung Edit', 'Cabor Kategori Tenaga Pendukung Delete'],
            ],
            [
                'name'       => 'Program Latihan',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pemeriksaan',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pemeriksaan Parameter',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Pemeriksaan Peserta',
                'permission'        => 'CRUD',
                'permission_common' => ['Pemeriksaan Peserta Kelola'],
            ],
            [
                'name'       => 'Pemeriksaan Peserta Parameter',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Pemeriksaan Khusus',
                'permission'        => 'CRUD',
                'permission_common' => ['Pemeriksaan Khusus Setup', 'Pemeriksaan Khusus Input Hasil Tes'],
            ],
            [
                'name'      => 'Turnamen',
                'permission'=> 'CRUD',
            ],
        ];

        $listCrud = ['Show', 'Add', 'Edit', 'Detail', 'Delete'];

        foreach ($categoryPermissions as $category) {
            $existingCategoryPermission = CategoryPermission::firstOrCreate(
                ['name' => $category['name']],
                ['name' => $category['name']]
            );

            $listPermission = [];

            if ($category['permission'] === 'CRUD') {
                foreach ($listCrud as $action) {
                    $listPermission[] = "{$category['name']} {$action}";
                }
            } else {
                foreach ($category['permission'] as $value) {
                    $listPermission[] = $value;
                }
            }

            if (isset($category['permission_common'])) {
                foreach ($category['permission_common'] as $value) {
                    $listPermission[] = $value;
                }
            }

            foreach ($listPermission as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'category_permission_id' => $existingCategoryPermission->id,
                        'name'                   => $permissionName,
                    ]
                );
            }
        }

        $this->command->info('CategoryPermissionSeeder table seeded!');
    }
}
