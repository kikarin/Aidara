<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\ProgramLatihan;
use App\Models\RencanaLatihan;
use App\Models\TargetLatihan;
use App\Models\TenagaPendukung;
use Illuminate\Database\Seeder;

class RencanaLatihanSeeder extends Seeder
{
    public function run(): void
    {
        $program = ProgramLatihan::first();
        if (! $program) {
            return;
        }

        $targetIds          = TargetLatihan::where('program_latihan_id', $program->id)->pluck('id')->toArray();
        $atletIds           = Atlet::limit(3)->pluck('id')->toArray();
        $pelatihIds         = Pelatih::limit(2)->pluck('id')->toArray();
        $tenagaPendukungIds = TenagaPendukung::limit(1)->pluck('id')->toArray();

        $rencana1 = RencanaLatihan::create([
            'program_latihan_id' => $program->id,
            'tanggal'            => '2025-08-05',
            'lokasi_latihan'     => 'Lapangan Utama',
            'materi'             => 'Latihan fisik dan teknik dasar',
            'catatan'            => 'Fokus pada pemanasan dan stretching',
        ]);
        $rencana1->targetLatihan()->sync(array_slice($targetIds, 0, 2));
        $rencana1->atlets()->sync($atletIds);
        $rencana1->pelatihs()->sync($pelatihIds);
        $rencana1->tenagaPendukung()->sync($tenagaPendukungIds);

        $rencana2 = RencanaLatihan::create([
            'program_latihan_id' => $program->id,
            'tanggal'            => '2025-08-12',
            'lokasi_latihan'     => 'Lapangan Indoor',
            'materi'             => 'Latihan strategi dan simulasi pertandingan',
            'catatan'            => 'Simulasi pertandingan internal',
        ]);
        $rencana2->targetLatihan()->sync(array_slice($targetIds, 1, 2));
        $rencana2->atlets()->sync(array_slice($atletIds, 0, 2));
        $rencana2->pelatihs()->sync($pelatihIds);
        $rencana2->tenagaPendukung()->sync($tenagaPendukungIds);
    }
}
