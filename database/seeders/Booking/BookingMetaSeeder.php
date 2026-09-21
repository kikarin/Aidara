<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingSetting;
use Illuminate\Database\Seeder;

class BookingMetaSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPriorityRules();
        $this->seedDocumentTypes();
        $this->seedAddons();
        $this->seedSettings();
    }

    private function seedPriorityRules(): void
    {
        $rows = [
            [
                'code' => 'pemda_dispora_upt',
                'name' => 'Kegiatan resmi Pemda / Dispora / UPT',
                'priority_order' => 1,
                'description' => 'Prioritas tertinggi konflik booking',
            ],
            [
                'code' => 'event_internasional',
                'name' => 'Tingkat event: Internasional',
                'priority_order' => 2,
                'description' => null,
            ],
            [
                'code' => 'event_nasional',
                'name' => 'Tingkat event: Nasional',
                'priority_order' => 3,
                'description' => null,
            ],
            [
                'code' => 'event_provinsi',
                'name' => 'Tingkat event: Provinsi',
                'priority_order' => 4,
                'description' => null,
            ],
            [
                'code' => 'event_kabupaten',
                'name' => 'Tingkat event: Kabupaten',
                'priority_order' => 5,
                'description' => null,
            ],
            [
                'code' => 'instansi_pemerintah_lain',
                'name' => 'Instansi pemerintah lain',
                'priority_order' => 6,
                'description' => null,
            ],
            [
                'code' => 'umum_komersial',
                'name' => 'Umum / komersial',
                'priority_order' => 7,
                'description' => 'Prioritas terendah',
            ],
        ];

        foreach ($rows as $row) {
            BookingPriorityRule::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'priority_order' => $row['priority_order'],
                    'description' => $row['description'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDocumentTypes(): void
    {
        BookingDocumentType::query()->updateOrCreate(
            ['code' => 'ktp'],
            [
                'name' => 'KTP',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    private function seedAddons(): void
    {
        // Harga sementara (placeholder) sampai UPT tetapkan angka resmi.
        BookingAddon::query()->updateOrCreate(
            ['code' => 'loading'],
            [
                'name' => 'Loading',
                'description' => 'Biaya loading (placeholder sementara — ubah via admin jika sudah ada angka resmi UPT)',
                'harga' => 500_000,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        BookingAddon::query()->updateOrCreate(
            ['code' => 'closing'],
            [
                'name' => 'Closing',
                'description' => 'Biaya closing (placeholder sementara — ubah via admin jika sudah ada angka resmi UPT)',
                'harga' => 500_000,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }

    private function seedSettings(): void
    {
        BookingSetting::setValue('payment_mode', 'manual', 'Mode pembayaran aktif: manual | bjb');
        BookingSetting::setValue('rekening_transfer', [
            'bank' => 'BJB Cabang Cibinong',
            'rekening' => '048.026020204.2',
            'atas_nama' => 'Rekening Kas Umum Daerah (RKUD) Kabupaten Bogor',
        ], 'Rekening transfer manual (dari tata cara UPT)');
        BookingSetting::setValue('kontak_klarifikasi', '085777183633', 'Nomor kontak admin untuk klarifikasi konflik');
        BookingSetting::setValue('branding_name', 'E-Booking', 'Nama tampilan modul');
        BookingSetting::setValue(
            'payment_expire_hours',
            48,
            'Tenggat jam menunggu bayar sejak payment dibuat; job booking:expire-payments'
        );
        BookingSetting::setValue('terms_tennis', [
            'title' => 'Tata Tertib Lapangan Tennis Kapten Muslihat',
            'points' => [
                'Wajib memarkir kendaraan roda 4 dan roda 2 di tempat yang ditentukan pengelola.',
                'Penyewa wajib menjaga kebersihan di area yang disewa.',
                'Dilarang merokok (konvensional atau elektrik) di area lapangan.',
                'Dilarang mengonsumsi minuman beralkohol atau obat-obatan terlarang.',
                'Dilarang melakukan kegiatan yang bersifat perjudian.',
                'Dilarang menitipkan barang bawaan kepada Petugas Keamanan atau Ball Boy.',
                'Penyewa wajib memastikan kondisi tubuh sehat untuk bermain tennis.',
                'Penyewa wajib bertanggung jawab mengganti kerusakan fasilitas karena kelalaian/penyalahgunaan.',
                'Dilarang membawa sound system tanpa izin Pengelola Lapangan.',
                'Setelah selesai bermain, penyewa diberi waktu maksimal 15 menit meninggalkan lapangan.',
            ],
        ], 'Tata tertib Tennis untuk checkbox persetujuan FE');
    }
}
