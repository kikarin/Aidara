<?php

namespace App\Services\SeleksiPpopm;

use App\Models\Atlet;
use App\Models\AtletKesehatan;
use App\Models\CaborKategori;
use App\Models\CaborKategoriAtlet;
use App\Models\MstKategoriPeserta;
use App\Models\SeleksiPendaftar;
use App\Support\SeleksiPpopm;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeleksiPromoteService
{
    public function promote(SeleksiPendaftar $pendaftar): Atlet
    {
        if ($pendaftar->status !== SeleksiPpopm::STATUS_LULUS) {
            throw new RuntimeException('Hanya pendaftar berstatus lulus yang dapat diangkat menjadi atlet.');
        }

        if ($pendaftar->atlet_id) {
            throw new RuntimeException('Pendaftar ini sudah diangkat menjadi atlet.');
        }

        return DB::transaction(function () use ($pendaftar) {
            $atlet = Atlet::create([
                'nik'            => $pendaftar->nik,
                'nisn'           => $pendaftar->nisn,
                'nama'           => $pendaftar->nama,
                'jenis_kelamin'  => $pendaftar->jenis_kelamin,
                'tempat_lahir'   => $pendaftar->tempat_lahir,
                'tanggal_lahir'  => $pendaftar->tanggal_lahir,
                'alamat'         => $pendaftar->alamat,
                'no_hp'          => $pendaftar->no_hp,
                'email'          => $pendaftar->email,
                'sekolah'        => $pendaftar->sekolah,
                'kelas_sekolah'  => $pendaftar->kelas_sekolah,
                'is_active'      => 1,
                'tanggal_bergabung' => now()->toDateString(),
            ]);

            $ppopm = MstKategoriPeserta::query()->where('nama', 'PPOPM')->first();
            if ($ppopm) {
                $exists = DB::table('atlet_kategori_peserta')
                    ->where('atlet_id', $atlet->id)
                    ->where('mst_kategori_peserta_id', $ppopm->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $exists) {
                    DB::table('atlet_kategori_peserta')->insert([
                        'atlet_id'                 => $atlet->id,
                        'mst_kategori_peserta_id'  => $ppopm->id,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);
                }
            }

            AtletKesehatan::create([
                'atlet_id'     => $atlet->id,
                'tinggi_badan' => (string) $pendaftar->tinggi_badan,
                'berat_badan'  => $pendaftar->berat_badan ? (string) $pendaftar->berat_badan : null,
            ]);

            $syarat = $pendaftar->caborSyarat;
            if ($syarat?->cabor_id) {
                $kategori = CaborKategori::query()
                    ->where('cabor_id', $syarat->cabor_id)
                    ->when($ppopm, fn ($q) => $q->where('kategori_peserta_id', $ppopm->id))
                    ->orderBy('id')
                    ->first()
                    ?? CaborKategori::query()->where('cabor_id', $syarat->cabor_id)->orderBy('id')->first();

                if ($kategori) {
                    CaborKategoriAtlet::firstOrCreate(
                        [
                            'cabor_kategori_id' => $kategori->id,
                            'atlet_id'          => $atlet->id,
                        ],
                        [
                            'cabor_id'    => $syarat->cabor_id,
                            'is_active'   => 1,
                        ]
                    );
                }
            }

            $pendaftar->atlet_id = $atlet->id;
            $pendaftar->save();

            return $atlet;
        });
    }
}
