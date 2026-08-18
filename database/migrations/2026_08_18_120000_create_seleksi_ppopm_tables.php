<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('seleksi_periode', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedSmallInteger('tahun');
            $table->date('tanggal_daftar_mulai');
            $table->date('tanggal_daftar_selesai');
            $table->date('tanggal_pengumuman_admin')->nullable();
            $table->date('tanggal_tes_mulai')->nullable();
            $table->date('tanggal_tes_selesai')->nullable();
            $table->date('tanggal_pleno')->nullable();
            $table->date('tanggal_pengumuman_hasil')->nullable();
            $table->string('tanggal_orientasi')->nullable();
            $table->string('lokasi_daftar')->nullable();
            $table->string('lokasi_tes')->nullable();
            $table->enum('status', ['draft', 'buka', 'tutup', 'selesai'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('seleksi_cabor_syarat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('periode_id');
            $table->unsignedBigInteger('cabor_id')->nullable();
            $table->string('kode');
            $table->string('nama_cabor');
            $table->enum('kelompok_map', ['permainan', 'bela_diri', 'terukur']);
            $table->unsignedSmallInteger('tahun_lahir_min');
            $table->unsignedSmallInteger('tahun_lahir_max');
            $table->unsignedSmallInteger('tinggi_min_putra')->nullable();
            $table->unsignedSmallInteger('tinggi_min_putri')->nullable();
            $table->json('tinggi_per_posisi')->nullable();
            $table->json('posisi')->nullable();
            $table->unsignedSmallInteger('kuota_putra')->default(0);
            $table->unsignedSmallInteger('kuota_putri')->default(0);
            $table->json('kuota_detail')->nullable();
            $table->json('jenis_kelamin_diizinkan')->nullable();
            $table->boolean('wajib_piagam')->default(false);
            $table->boolean('wajib_berenang')->default(false);
            $table->boolean('wajib_dua_posisi')->default(false);
            $table->unsignedSmallInteger('prioritas_tinggi')->nullable();
            $table->json('toleransi_tinggi')->nullable();
            $table->text('syarat_tambahan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('periode_id')->references('id')->on('seleksi_periode')->onDelete('cascade');
            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('set null');
            $table->unique(['periode_id', 'kode']);
        });

        Schema::create('seleksi_pendaftar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('periode_id');
            $table->unsignedBigInteger('cabor_syarat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nomor_tes')->nullable();
            $table->string('nama');
            $table->string('nik', 32)->nullable();
            $table->string('nisn', 32)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir');
            $table->text('alamat')->nullable();
            $table->string('no_hp', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('sekolah')->nullable();
            $table->string('kelas_sekolah')->nullable();
            $table->boolean('asal_kabupaten_bogor')->default(true);
            $table->decimal('tinggi_badan', 5, 1);
            $table->decimal('berat_badan', 5, 1)->nullable();
            $table->string('posisi')->nullable();
            $table->string('nomor_kelas')->nullable();
            $table->decimal('vertical_jump', 5, 1)->nullable();
            $table->boolean('bisa_dua_posisi')->default(false);
            $table->boolean('bisa_berenang')->default(false);
            $table->boolean('kuasai_poomsae')->default(false);
            $table->boolean('bersedia_pindah_domisili')->default(false);
            $table->boolean('setuju_perjanjian')->default(false);
            $table->string('status', 40)->default('submitted');
            $table->text('alasan_tolak')->nullable();
            $table->decimal('skor_kecabangan_mentah', 4, 2)->nullable();
            $table->decimal('skor_kecabangan', 6, 2)->nullable();
            $table->decimal('skor_fisik', 6, 2)->nullable();
            $table->decimal('skor_akademik', 6, 2)->nullable();
            $table->decimal('skor_psikologi', 6, 2)->nullable();
            $table->boolean('psikologi_rekomendasi')->nullable();
            $table->boolean('kesehatan_layak')->nullable();
            $table->boolean('antropometri_layak')->nullable();
            $table->decimal('nilai_akhir', 6, 2)->nullable();
            $table->unsignedInteger('ranking')->nullable();
            $table->unsignedBigInteger('atlet_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('periode_id')->references('id')->on('seleksi_periode')->onDelete('cascade');
            $table->foreign('cabor_syarat_id')->references('id')->on('seleksi_cabor_syarat')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('set null');
            $table->unique('nomor_tes');
            $table->index(['periode_id', 'status']);
            $table->index(['cabor_syarat_id', 'status']);
        });

        Schema::create('seleksi_berkas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pendaftar_id');
            $table->string('jenis', 40);
            $table->string('file_path');
            $table->string('file_nama')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pendaftar_id')->references('id')->on('seleksi_pendaftar')->onDelete('cascade');
            $table->index(['pendaftar_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seleksi_berkas');
        Schema::dropIfExists('seleksi_pendaftar');
        Schema::dropIfExists('seleksi_cabor_syarat');
        Schema::dropIfExists('seleksi_periode');
    }
};
