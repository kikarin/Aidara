<?php

namespace Database\Seeders;

use App\Models\MstJenisDokumen;
use App\Models\TenagaPendukung;
use App\Models\TenagaPendukungDokumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TenagaPendukungDokumenSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TenagaPendukungDokumen::truncate();
        Schema::enableForeignKeyConstraints();

        $tpIds          = TenagaPendukung::pluck('id')->toArray();
        $jenisDokumenId = MstJenisDokumen::first()->id ?? 1;
        $data           = [
            [
                'tenaga_pendukung_id' => $tpIds[0] ?? 1,
                'jenis_dokumen_id'    => $jenisDokumenId,
                'nomor'               => 'TPDOK001',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[1] ?? 2,
                'jenis_dokumen_id'    => $jenisDokumenId,
                'nomor'               => 'TPDOK002',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[2] ?? 3,
                'jenis_dokumen_id'    => $jenisDokumenId,
                'nomor'               => 'TPDOK003',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[3] ?? 4,
                'jenis_dokumen_id'    => $jenisDokumenId,
                'nomor'               => 'TPDOK004',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[4] ?? 5,
                'jenis_dokumen_id'    => $jenisDokumenId,
                'nomor'               => 'TPDOK005',
            ],
        ];
        foreach ($data as $item) {
            TenagaPendukungDokumen::create($item);
        }
    }
}
