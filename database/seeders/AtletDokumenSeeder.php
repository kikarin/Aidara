<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\AtletDokumen;
use App\Models\MstJenisDokumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletDokumenSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        AtletDokumen::truncate();
        Schema::enableForeignKeyConstraints();

        $atletIds       = Atlet::pluck('id')->toArray();
        $jenisDokumenId = MstJenisDokumen::first()->id ?? 1;
        $data           = [
            [
                'atlet_id'         => $atletIds[0] ?? 1,
                'jenis_dokumen_id' => $jenisDokumenId,
                'nomor'            => 'DOK001',
            ],
            [
                'atlet_id'         => $atletIds[1] ?? 2,
                'jenis_dokumen_id' => $jenisDokumenId,
                'nomor'            => 'DOK002',
            ],
            [
                'atlet_id'         => $atletIds[2] ?? 3,
                'jenis_dokumen_id' => $jenisDokumenId,
                'nomor'            => 'DOK003',
            ],
            [
                'atlet_id'         => $atletIds[3] ?? 4,
                'jenis_dokumen_id' => $jenisDokumenId,
                'nomor'            => 'DOK004',
            ],
            [
                'atlet_id'         => $atletIds[4] ?? 5,
                'jenis_dokumen_id' => $jenisDokumenId,
                'nomor'            => 'DOK005',
            ],
        ];
        foreach ($data as $item) {
            AtletDokumen::create($item);
        }
    }
}
