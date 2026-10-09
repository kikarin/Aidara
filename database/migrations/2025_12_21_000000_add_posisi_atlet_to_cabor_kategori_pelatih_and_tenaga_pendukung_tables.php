<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Tambah kolom posisi_atlet ke cabor_kategori_pelatih
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->string('posisi_atlet')->nullable()->after('jenis_pelatih');
        });

        // Tambah kolom posisi_atlet ke cabor_kategori_tenaga_pendukung
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->string('posisi_atlet')->nullable()->after('jenis_tenaga_pendukung');
        });
    }

    public function down(): void
    {
        // Rollback untuk cabor_kategori_pelatih
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->dropColumn('posisi_atlet');
        });

        // Rollback untuk cabor_kategori_tenaga_pendukung
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->dropColumn('posisi_atlet');
        });
    }
};

