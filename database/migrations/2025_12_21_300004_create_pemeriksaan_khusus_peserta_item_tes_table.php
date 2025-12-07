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
        Schema::create('pemeriksaan_khusus_peserta_item_tes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pemeriksaan_khusus_id');
            $table->unsignedBigInteger('pemeriksaan_khusus_peserta_id');
            $table->unsignedBigInteger('pemeriksaan_khusus_item_tes_id');
            $table->string('nilai')->nullable()->comment('Hasil tes aktual');
            $table->decimal('persentase_performa', 5, 2)->nullable()->comment('Persentase untuk perhitungan (max 100%)');
            $table->decimal('persentase_riil', 5, 2)->nullable()->comment('Persentase riil (bisa > 100%, hanya untuk display)');
            $table->enum('predikat', ['sangat_kurang', 'kurang', 'sedang', 'mendekati_target', 'target'])->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pemeriksaan_khusus_id', 'fk_pk_peserta_item_pk_id')->references('id')->on('pemeriksaan_khusus')->onDelete('cascade');
            $table->foreign('pemeriksaan_khusus_peserta_id', 'fk_pk_peserta_item_peserta')->references('id')->on('pemeriksaan_khusus_peserta')->onDelete('cascade');
            $table->foreign('pemeriksaan_khusus_item_tes_id', 'fk_pk_peserta_item_tes')->references('id')->on('pemeriksaan_khusus_item_tes')->onDelete('cascade');
            
            $table->unique(['pemeriksaan_khusus_peserta_id', 'pemeriksaan_khusus_item_tes_id'], 'unique_peserta_item_tes');
            $table->index('pemeriksaan_khusus_id', 'idx_pk_peserta_item_pk_id');
            $table->index('pemeriksaan_khusus_peserta_id', 'idx_pk_peserta_item_peserta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_khusus_peserta_item_tes');
    }
};

