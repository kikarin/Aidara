<?php

namespace Database\Seeders;

use App\Models\Pelatih;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PelatihSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Pelatih::truncate();
        Schema::enableForeignKeyConstraints();

        $firstNamesL = ['Agus', 'Joko', 'Dedi', 'Rudi', 'Bambang'];
        $firstNamesP = ['Sri', 'Maya', 'Dewi', 'Rina', 'Ayu'];
        $lastNames   = ['Salim', 'Rahayu', 'Susilo', 'Mulyadi', 'Hartono', 'Wibowo'];
        $cities      = ['Medan', 'Padang', 'Solo', 'Malang', 'Bogor', 'Jakarta', 'Bandung'];

        $records = [];
        for ($i = 0; $i < 10; $i++) {
            $isFemale  = ($i % 2) === 1;
            $first     = $isFemale ? $firstNamesP[$i % count($firstNamesP)] : $firstNamesL[$i % count($firstNamesL)];
            $last      = $lastNames[$i % count($lastNames)];
            $name      = $first.' '.$last;
            $nik       = str_pad((string) (9876543210123000 + $i), 16, '0', STR_PAD_LEFT);
            $email     = strtolower(str_replace(' ', '.', $name)).$i.'@example.com';
            $lahir     = sprintf('198%u-%02u-%02u', ($i % 10), (($i % 12) + 1), (($i % 28) + 1));
            $gabung    = sprintf('201%u-%02u-%02u', ($i % 10), ((($i + 1) % 12) + 1), ((($i + 2) % 28) + 1));
            $records[] = [
                'nik'               => $nik,
                'nama'              => $name,
                'jenis_kelamin'     => $isFemale ? 'P' : 'L',
                'tempat_lahir'      => $cities[$i % count($cities)],
                'tanggal_lahir'     => $lahir,
                'tanggal_bergabung' => $gabung,
                'alamat'            => 'Jl. Pelatih No. '.($i + 1),
                'no_hp'             => '08'.str_pad((string) (1298765432 + $i), 10, '0', STR_PAD_LEFT),
                'email'             => $email,
                'is_active'         => 1,
            ];
        }

        foreach ($records as $row) {
            Pelatih::create($row);
        }
    }
}
