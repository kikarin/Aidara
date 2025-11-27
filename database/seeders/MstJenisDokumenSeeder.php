<?php

namespace Database\Seeders;

use App\Models\MstJenisDokumen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MstJenisDokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        MstJenisDokumen::truncate();
        Schema::enableForeignKeyConstraints();

        $jenisDokumens = [
            ['nama' => 'Kartu Tanda Penduduk (KTP)'],
            ['nama' => 'Kartu Keluarga (KK)'],
            ['nama' => 'Ijazah'],
            ['nama' => 'Surat Keterangan Sehat'],
            ['nama' => 'Pas Foto'],
            ['nama' => 'Surat Rekomendasi'],
        ];

        foreach ($jenisDokumens as $jenisDokumen) {
            MstJenisDokumen::create($jenisDokumen);
        }
    }
}
