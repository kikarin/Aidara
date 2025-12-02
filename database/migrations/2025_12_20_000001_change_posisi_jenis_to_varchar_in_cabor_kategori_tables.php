<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // 1. Cabor Kategori Atlet - ubah posisi_atlet_id menjadi posisi_atlet (varchar)
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['posisi_atlet_id']);
            // Tambah kolom baru sebagai varchar
            $table->string('posisi_atlet')->nullable()->after('atlet_id');
        });
        
        // Migrate data dari mst_posisi_atlet ke varchar
        DB::statement('
            UPDATE cabor_kategori_atlet cka
            INNER JOIN mst_posisi_atlet mpa ON cka.posisi_atlet_id = mpa.id
            SET cka.posisi_atlet = mpa.nama
            WHERE cka.posisi_atlet_id IS NOT NULL
        ');
        
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            // Drop kolom lama
            $table->dropColumn('posisi_atlet_id');
        });

        // 2. Cabor Kategori Pelatih - ubah jenis_pelatih_id menjadi jenis_pelatih (varchar)
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['jenis_pelatih_id']);
            // Tambah kolom baru sebagai varchar
            $table->string('jenis_pelatih')->nullable()->after('pelatih_id');
        });
        
        // Migrate data dari mst_jenis_pelatih ke varchar
        DB::statement('
            UPDATE cabor_kategori_pelatih ckp
            INNER JOIN mst_jenis_pelatih mjp ON ckp.jenis_pelatih_id = mjp.id
            SET ckp.jenis_pelatih = mjp.nama
            WHERE ckp.jenis_pelatih_id IS NOT NULL
        ');
        
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            // Drop kolom lama
            $table->dropColumn('jenis_pelatih_id');
        });

        // 3. Cabor Kategori Tenaga Pendukung - ubah jenis_tenaga_pendukung_id menjadi jenis_tenaga_pendukung (varchar)
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            // Drop foreign key dulu (menggunakan nama custom 'fk_jenis_tp_id')
            $table->dropForeign('fk_jenis_tp_id');
            // Tambah kolom baru sebagai varchar
            $table->string('jenis_tenaga_pendukung')->nullable()->after('tenaga_pendukung_id');
        });
        
        // Migrate data dari mst_jenis_tenaga_pendukung ke varchar
        DB::statement('
            UPDATE cabor_kategori_tenaga_pendukung cktp
            INNER JOIN mst_jenis_tenaga_pendukung mjtp ON cktp.jenis_tenaga_pendukung_id = mjtp.id
            SET cktp.jenis_tenaga_pendukung = mjtp.nama
            WHERE cktp.jenis_tenaga_pendukung_id IS NOT NULL
        ');
        
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            // Drop kolom lama
            $table->dropColumn('jenis_tenaga_pendukung_id');
        });
    }

    public function down(): void
    {
        // Rollback untuk cabor_kategori_atlet
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->dropColumn('posisi_atlet');
        });
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->unsignedBigInteger('posisi_atlet_id')->nullable()->after('atlet_id');
            $table->foreign('posisi_atlet_id')->references('id')->on('mst_posisi_atlet')->onDelete('set null');
        });

        // Rollback untuk cabor_kategori_pelatih
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->dropColumn('jenis_pelatih');
        });
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_pelatih_id')->after('pelatih_id');
            $table->foreign('jenis_pelatih_id')->references('id')->on('mst_jenis_pelatih')->onDelete('cascade');
        });

        // Rollback untuk cabor_kategori_tenaga_pendukung
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->dropColumn('jenis_tenaga_pendukung');
        });
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_tenaga_pendukung_id')->after('tenaga_pendukung_id');
            $table->foreign('jenis_tenaga_pendukung_id', 'fk_jenis_tp_id')
                ->references('id')->on('mst_jenis_tenaga_pendukung')->onDelete('cascade');
        });
    }
};

