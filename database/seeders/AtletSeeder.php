<?php

namespace Database\Seeders;

use App\Models\Atlet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Atlet::truncate();
        Schema::enableForeignKeyConstraints();

        $firstNamesL = ['Budi', 'Andi', 'Rizky', 'Agus', 'Joko', 'Dedi', 'Rudi', 'Hendra', 'Fajar', 'Yoga'];
        $firstNamesP = ['Siti', 'Dewi', 'Maya', 'Rina', 'Nina', 'Ayu', 'Intan', 'Lia', 'Rani', 'Putri'];
        $lastNames   = ['Santoso', 'Wijaya', 'Pratama', 'Saputra', 'Lestari', 'Sari', 'Hartono', 'Setiawan', 'Permata', 'Mahardika'];

        $cities = ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Bogor', 'Malang', 'Solo', 'Makassar'];

        $records = [];
        $idx     = 0;
        while (count($records) < 20) {
            $isFemale  = ($idx % 2) === 1;
            $first     = $isFemale ? $firstNamesP[$idx % count($firstNamesP)] : $firstNamesL[$idx % count($firstNamesL)];
            $last      = $lastNames[$idx % count($lastNames)];
            $name      = $first.' '.$last;
            $nik       = str_pad((string) (1234567890123000 + $idx), 16, '0', STR_PAD_LEFT);
            $email     = strtolower(str_replace(' ', '.', $name)).$idx.'@example.com';
            $lahir     = sprintf('200%u-%02u-%02u', ($idx % 5), (($idx % 12) + 1), (($idx % 28) + 1));
            $gabung    = sprintf('202%u-%02u-%02u', ($idx % 5), ((($idx + 1) % 12) + 1), ((($idx + 2) % 28) + 1));
            $records[] = [
                'nik'               => $nik,
                'nama'              => $name,
                'jenis_kelamin'     => $isFemale ? 'P' : 'L',
                'tempat_lahir'      => $cities[$idx % count($cities)],
                'tanggal_lahir'     => $lahir,
                'tanggal_bergabung' => $gabung,
                'alamat'            => 'Jl. Contoh No. '.($idx + 1),
                'no_hp'             => '08'.str_pad((string) (1234567890 + $idx), 10, '0', STR_PAD_LEFT),
                'email'             => $email,
                'is_active'         => 1,
            ];
            $idx++;
        }

        foreach ($records as $row) {
            Atlet::create($row);
        }
    }
}
