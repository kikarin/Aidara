<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pelatih_prestasi', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_prestasi_pelatih_id')->nullable()->after('pelatih_id');
            $table->unsignedBigInteger('kategori_atlet_id')->nullable()->after('kategori_prestasi_pelatih_id');

            $table->foreign('kategori_prestasi_pelatih_id')->references('id')->on('mst_kategori_prestasi_pelatih')->onDelete('set null');
            $table->foreign('kategori_atlet_id')->references('id')->on('mst_kategori_atlet')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelatih_prestasi', function (Blueprint $table) {
            $table->dropForeign(['kategori_prestasi_pelatih_id']);
            $table->dropForeign(['kategori_atlet_id']);
            $table->dropColumn(['kategori_prestasi_pelatih_id', 'kategori_atlet_id']);
        });
    }
};
