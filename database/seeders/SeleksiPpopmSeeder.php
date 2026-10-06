<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CategoryPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SeleksiCaborSyarat;
use App\Models\SeleksiPeriode;
use App\Models\UsersMenu;
use App\Repositories\UsersMenuRepository;
use Illuminate\Database\Seeder;

class SeleksiPpopmSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = $this->seedPermissions();
        $this->seedMenu($permissions['Seleksi PPOPM Show'] ?? null);
        $this->assignPermissions($permissions);
        $periode = $this->seedPeriode();
        $this->seedSyarat($periode);

        app(UsersMenuRepository::class)->invalidateMenusCache();
    }

    /**
     * @return array<string, Permission>
     */
    private function seedPermissions(): array
    {
        $category = CategoryPermission::query()->firstOrCreate([
            'name' => 'Seleksi PPOPM',
        ]);

        $names = [
            'Seleksi PPOPM Show',
            'Seleksi PPOPM Add',
            'Seleksi PPOPM Edit',
            'Seleksi PPOPM Detail',
            'Seleksi PPOPM Delete',
            'Seleksi PPOPM Verifikasi',
            'Seleksi PPOPM Input Tes',
            'Seleksi PPOPM Pleno',
        ];

        $permissions = [];
        foreach ($names as $name) {
            $permissions[$name] = Permission::query()->updateOrCreate(
                ['name' => $name],
                ['category_permission_id' => $category->id],
            );
        }

        return $permissions;
    }

    private function seedMenu(?Permission $permission): void
    {
        UsersMenu::query()->updateOrCreate(
            ['kode' => 'SELEKSI-PPOPM'],
            [
                'nama'          => 'Seleksi PPOPM',
                'url'           => '/seleksi-ppopm',
                'icon'          => 'ClipboardList',
                'rel'           => 0,
                'urutan'        => 33,
                'permission_id' => $permission?->id,
            ],
        );
    }

    /**
     * @param  array<string, Permission>  $permissions
     */
    private function assignPermissions(array $permissions): void
    {
        foreach ([1, 11] as $roleId) {
            $role = Role::query()->find($roleId);
            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission->name)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    private function seedPeriode(): SeleksiPeriode
    {
        return SeleksiPeriode::query()->updateOrCreate(
            ['tahun' => 2026],
            [
                'nama'                     => 'Seleksi Atlet UPT PPOPM Dispora Kabupaten Bogor Tahun 2026',
                'tanggal_daftar_mulai'     => '2026-05-04',
                'tanggal_daftar_selesai'   => '2026-05-07',
                'tanggal_pengumuman_admin' => '2026-05-13',
                'tanggal_tes_mulai'        => '2026-05-18',
                'tanggal_tes_selesai'      => '2026-05-22',
                'tanggal_pleno'            => '2026-06-02',
                'tanggal_pengumuman_hasil' => '2026-06-05',
                'tanggal_orientasi'        => 'Juli 2026',
                'lokasi_daftar'            => 'Wisma Atlet PPOPM (Gor Karadenan Cibinong)',
                'lokasi_tes'               => 'Pakansari / PPOPM',
                'status'                   => 'buka',
            ],
        );
    }

    private function seedSyarat(SeleksiPeriode $periode): void
    {
        foreach ($this->syaratData() as $row) {
            $cabor = Cabor::query()->where('nama', $row['cabor_nama_master'])->first();
            unset($row['cabor_nama_master']);
            $row['periode_id'] = $periode->id;
            $row['cabor_id']   = $cabor?->id;

            SeleksiCaborSyarat::query()->updateOrCreate(
                ['periode_id' => $periode->id, 'kode' => $row['kode']],
                $row,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function syaratData(): array
    {
        return [
            [
                'kode'                    => 'basket',
                'nama_cabor'              => 'Basket',
                'cabor_nama_master'       => 'Bola Basket',
                'kelompok_map'            => 'permainan',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => 160,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center'],
                'kuota_putra'             => 0,
                'kuota_putri'             => 1,
                'kuota_detail'            => null,
                'jenis_kelamin_diizinkan' => ['P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => 170,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Prioritas tinggi badan 170 cm atau lebih.',
            ],
            [
                'kode'                    => 'sepak-bola',
                'nama_cabor'              => 'Sepak Bola',
                'cabor_nama_master'       => 'Sepak Bola',
                'kelompok_map'            => 'permainan',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2012,
                'tinggi_min_putra'        => 165,
                'tinggi_min_putri'        => null,
                'tinggi_per_posisi'       => [
                    'Gelandang'  => 165,
                    'Bek'        => 170,
                    'Penyerang'  => 170,
                    'Penjaga Gawang' => 170,
                ],
                'posisi'                  => ['Gelandang', 'Bek', 'Penyerang', 'Penjaga Gawang'],
                'kuota_putra'             => 4,
                'kuota_putri'             => 0,
                'kuota_detail'            => [
                    'Gelandang'      => 1,
                    'Bek'            => 1,
                    'Penyerang'      => 1,
                    'Penjaga Gawang' => 1,
                ],
                'jenis_kelamin_diizinkan' => ['L'],
                'wajib_piagam'            => true,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => true,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Mampu bermain minimal 2 posisi, kedua kaki kuat menendang, serta menyertakan piagam minimal juara 2 tingkat kabupaten.',
            ],
            [
                'kode'                    => 'voli-indoor',
                'nama_cabor'              => 'Voli Indoor',
                'cabor_nama_master'       => 'Voli Indoor',
                'kelompok_map'            => 'permainan',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2013,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => 170,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Spiker'],
                'kuota_putra'             => 0,
                'kuota_putri'             => 1,
                'kuota_detail'            => null,
                'jenis_kelamin_diizinkan' => ['P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => ['min' => 165, 'max' => 169, 'vertical_jump' => 45],
                'syarat_tambahan'         => 'Toleransi tinggi 165–169 cm jika vertical jump minimal 45 cm.',
            ],
            [
                'kode'                    => 'voli-pasir',
                'nama_cabor'              => 'Voli Pasir',
                'cabor_nama_master'       => 'Voli Pasir',
                'kelompok_map'            => 'permainan',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => 170,
                'tinggi_min_putri'        => 163,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Semua posisi (serang/defend)'],
                'kuota_putra'             => 2,
                'kuota_putri'             => 1,
                'kuota_detail'            => null,
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Mampu bermain di semua posisi (attacking/defending).',
            ],
            [
                'kode'                    => 'taekwondo-kyorugi',
                'nama_cabor'              => 'Taekwondo Kyorugi',
                'cabor_nama_master'       => 'Taekwondo',
                'kelompok_map'            => 'bela_diri',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => 170,
                'tinggi_min_putri'        => 165,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Kyorugi (semua kelas)'],
                'kuota_putra'             => 3,
                'kuota_putri'             => 3,
                'kuota_detail'            => ['all' => 3],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Kuota kyorugi 3 atlet putra/putri (semua kelas).',
            ],
            [
                'kode'                    => 'taekwondo-poomsae',
                'nama_cabor'              => 'Taekwondo Poomsae',
                'cabor_nama_master'       => 'Taekwondo Poomsae',
                'kelompok_map'            => 'bela_diri',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => 155,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Poomsae'],
                'kuota_putra'             => 0,
                'kuota_putri'             => 1,
                'kuota_detail'            => null,
                'jenis_kelamin_diizinkan' => ['P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Menguasai gerakan Taeguk 4 sampai Pyongwon.',
            ],
            [
                'kode'                    => 'pencak-silat',
                'nama_cabor'              => 'Pencak Silat',
                'cabor_nama_master'       => 'Pencak Silat',
                'kelompok_map'            => 'bela_diri',
                'tahun_lahir_min'         => 2012,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => 160,
                'tinggi_min_putri'        => 155,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Semua kelas'],
                'kuota_putra'             => 1,
                'kuota_putri'             => 1,
                'kuota_detail'            => ['all' => 1],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => true,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Menyerahkan data/sertifikat prestasi dan rekomendasi sekolah/perguruan.',
            ],
            [
                'kode'                    => 'karate',
                'nama_cabor'              => 'Karate',
                'cabor_nama_master'       => 'Karate',
                'kelompok_map'            => 'bela_diri',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => null,
                'tinggi_per_posisi'       => null,
                'posisi'                  => ['Kata', 'Kumite'],
                'kuota_putra'             => 3,
                'kuota_putri'             => 3,
                'kuota_detail'            => ['all' => 3],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Kategori Kata & Kumite, kuota 3 putra/putri.',
            ],
            [
                'kode'                    => 'atletik',
                'nama_cabor'              => 'Atletik',
                'cabor_nama_master'       => 'Atletik',
                'kelompok_map'            => 'terukur',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => 160,
                'tinggi_min_putri'        => 155,
                'tinggi_per_posisi'       => null,
                'posisi'                  => [
                    'Sprint gawang putra',
                    'Sprint gawang putri',
                    'Lari 5000m putra',
                    'Lari 3000m putri',
                    'Tolak peluru putra',
                    'Tolak peluru putri',
                    'Lari 800m putra',
                ],
                'kuota_putra'             => 4,
                'kuota_putri'             => 3,
                'kuota_detail'            => [
                    'Sprint gawang putra' => 1,
                    'Sprint gawang putri' => 1,
                    'Lari 5000m putra'    => 1,
                    'Lari 3000m putri'    => 1,
                    'Tolak peluru putra'  => 1,
                    'Tolak peluru putri'  => 1,
                    'Lari 800m putra'     => 1,
                ],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Kuota pecah per nomor sesuai JUKNIS.',
            ],
            [
                'kode'                    => 'angkat-besi',
                'nama_cabor'              => 'Angkat Besi',
                'cabor_nama_master'       => 'Angkat Besi',
                'kelompok_map'            => 'terukur',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => null,
                'tinggi_per_posisi'       => null,
                'posisi'                  => null,
                'kuota_putra'             => 1,
                'kuota_putri'             => 1,
                'kuota_detail'            => ['all' => 1],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Kuota 1 putra/putri.',
            ],
            [
                'kode'                    => 'dayung',
                'nama_cabor'              => 'Dayung',
                'cabor_nama_master'       => 'Dayung',
                'kelompok_map'            => 'terukur',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => 160,
                'tinggi_min_putri'        => 155,
                'tinggi_per_posisi'       => null,
                'posisi'                  => null,
                'kuota_putra'             => 2,
                'kuota_putri'             => 2,
                'kuota_detail'            => ['all' => 2],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => true,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Wajib bisa berenang. Kelahiran 2014 diperbolehkan belum menguasai teknik, tetapi wajib bisa berenang.',
            ],
            [
                'kode'                    => 'tinju',
                'nama_cabor'              => 'Tinju',
                'cabor_nama_master'       => 'Tinju',
                'kelompok_map'            => 'bela_diri',
                'tahun_lahir_min'         => 2011,
                'tahun_lahir_max'         => 2014,
                'tinggi_min_putra'        => null,
                'tinggi_min_putri'        => null,
                'tinggi_per_posisi'       => null,
                'posisi'                  => null,
                'kuota_putra'             => 2,
                'kuota_putri'             => 2,
                'kuota_detail'            => ['all' => 2],
                'jenis_kelamin_diizinkan' => ['L', 'P'],
                'wajib_piagam'            => false,
                'wajib_berenang'          => false,
                'wajib_dua_posisi'        => false,
                'prioritas_tinggi'        => null,
                'toleransi_tinggi'        => null,
                'syarat_tambahan'         => 'Kuota 2 putra/putri.',
            ],
        ];
    }
}
