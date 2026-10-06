<?php

namespace App\Support;

class SeleksiPpopm
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_DITOLAK_ADMIN = 'ditolak_admin';

    public const STATUS_LULUS_ADMINISTRASI = 'lulus_administrasi';

    public const STATUS_GUGUR_KESEHATAN = 'gugur_kesehatan';

    public const STATUS_GUGUR_ANTROPOMETRI = 'gugur_antropometri';

    public const STATUS_GUGUR_KECABANGAN = 'gugur_kecabangan';

    public const STATUS_LULUS = 'lulus';

    public const STATUS_OBSERVASI = 'observasi';

    public const STATUS_TIDAK_LULUS = 'tidak_lulus';

    public const BERKAS_WAJIB = ['kk', 'akta', 'bpjs', 'ijazah', 'surat_sehat', 'rekomendasi'];

    public const BERKAS_LABELS = [
        'kk'           => 'Kartu Keluarga',
        'akta'         => 'Akta Kelahiran',
        'bpjs'         => 'BPJS Kesehatan (aktif)',
        'ijazah'       => 'Ijazah terakhir',
        'surat_sehat'  => 'Surat keterangan sehat',
        'rekomendasi'  => 'Surat rekomendasi sekolah',
        'piagam'       => 'Piagam / sertifikat kejuaraan',
        'perjanjian'   => 'Perjanjian PPOPM',
    ];

    public const MAP_LABELS = [
        'permainan'  => 'Merah — Cabor Permainan',
        'bela_diri'  => 'Biru — Cabor Bela Diri',
        'terukur'    => 'Hijau — Cabor Terukur',
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT               => 'Draft',
        self::STATUS_SUBMITTED           => 'Menunggu verifikasi',
        self::STATUS_DITOLAK_ADMIN       => 'Ditolak administrasi',
        self::STATUS_LULUS_ADMINISTRASI  => 'Lulus administrasi',
        self::STATUS_GUGUR_KESEHATAN     => 'Gugur kesehatan',
        self::STATUS_GUGUR_ANTROPOMETRI  => 'Gugur antropometri',
        self::STATUS_GUGUR_KECABANGAN    => 'Gugur kecabangan',
        self::STATUS_LULUS               => 'Lulus',
        self::STATUS_OBSERVASI           => 'Observasi / perbaikan',
        self::STATUS_TIDAK_LULUS         => 'Tidak lulus',
    ];

    public const BOBOT_KECABANGAN = 0.40;

    public const BOBOT_FISIK = 0.25;

    public const BOBOT_AKADEMIK = 0.10;

    public const BOBOT_PSIKOLOGI = 0.25;

    public const AMBANG_KECABANGAN = 70;

    public const AMBANG_LULUS = 70;

    public const AMBANG_OBSERVASI = 50;
}
