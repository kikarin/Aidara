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
        Schema::table('atlet_prestasi', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_peserta_id')->nullable()->after('atlet_id')->comment('Diambil otomatis dari atlet');
            $table->enum('jenis_prestasi', ['individu', 'ganda/mixed/beregu/double'])->default('individu')->after('kategori_peserta_id');
            $table->string('juara')->nullable()->after('jenis_prestasi');
            $table->enum('medali', ['Emas', 'Perak', 'Perunggu'])->nullable()->after('juara');
            $table->unsignedBigInteger('prestasi_group_id')->nullable()->after('medali')->comment('ID untuk grouping prestasi beregu');
            
            $table->foreign('kategori_peserta_id')->references('id')->on('mst_kategori_peserta')->onDelete('set null');
            $table->index('prestasi_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atlet_prestasi', function (Blueprint $table) {
            $table->dropForeign(['kategori_peserta_id']);
            $table->dropIndex(['prestasi_group_id']);
            $table->dropColumn(['kategori_peserta_id', 'jenis_prestasi', 'juara', 'medali', 'prestasi_group_id']);
        });
    }
};

