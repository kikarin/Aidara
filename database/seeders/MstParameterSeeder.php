<?php

namespace Database\Seeders;

use App\Models\MstParameter;
use Illuminate\Database\Seeder;

class MstParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama'      => 'Tekanan Darah',
                'satuan' => 'mmHg',
            ],
            ['nama'      => 'Denyut Nadi',
                'satuan' => 'bpm',
            ],
            ['nama'      => 'Suhu Tubuh',
                'satuan' => '°C',
            ],
        ];

        foreach ($data as $item) {
            MstParameter::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
