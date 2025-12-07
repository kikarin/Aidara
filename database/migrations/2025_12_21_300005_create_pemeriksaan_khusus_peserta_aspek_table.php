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
        Schema::create('pemeriksaan_khusus_peserta_aspek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pemeriksaan_khusus_id');
            $table->unsignedBigInteger('pemeriksaan_khusus_peserta_id');
            $table->unsignedBigInteger('pemeriksaan_khusus_aspek_id');
            $table->decimal('nilai_performa', 5, 2)->nullable()->comment('Rata-rata persentase item tes di aspek ini (max 100%)');
            $table->enum('predikat', ['sangat_kurang', 'kurang', 'sedang', 'mendekati_target', 'target'])->nullable();
            $table->timestamps();

            $table->foreign('pemeriksaan_khusus_id', 'fk_pk_peserta_aspek_pk_id')->references('id')->on('pemeriksaan_khusus')->onDelete('cascade');
            $table->foreign('pemeriksaan_khusus_peserta_id', 'fk_pk_peserta_aspek_peserta')->references('id')->on('pemeriksaan_khusus_peserta')->onDelete('cascade');
            $table->foreign('pemeriksaan_khusus_aspek_id', 'fk_pk_peserta_aspek_aspek')->references('id')->on('pemeriksaan_khusus_aspek')->onDelete('cascade');
            
            $table->unique(['pemeriksaan_khusus_peserta_id', 'pemeriksaan_khusus_aspek_id'], 'unique_peserta_aspek');
            $table->index('pemeriksaan_khusus_id', 'idx_pk_peserta_aspek_pk_id');
            $table->index('pemeriksaan_khusus_peserta_id', 'idx_pk_peserta_aspek_peserta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_khusus_peserta_aspek');
    }
};

