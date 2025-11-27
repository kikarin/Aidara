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
        if (Schema::hasTable('tenaga_pendukung_kategori_peserta')) {
            return;
        }

        Schema::create('tenaga_pendukung_kategori_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->unsignedBigInteger('mst_kategori_peserta_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('tenaga_pendukung_id', 'tpp_tp_id_fk')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
            $table->foreign('mst_kategori_peserta_id', 'tpp_mkp_id_fk')->references('id')->on('mst_kategori_peserta')->onDelete('cascade');
            $table->unique(['tenaga_pendukung_id', 'mst_kategori_peserta_id'], 'unique_tenaga_pendukung_kategori_peserta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenaga_pendukung_kategori_peserta');
    }
};
