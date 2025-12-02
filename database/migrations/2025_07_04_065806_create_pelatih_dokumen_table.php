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
        Schema::create('pelatih_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelatih_id');
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable();
            $table->string('nomor')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pelatih_id')->references('id')->on('pelatihs')->onDelete('cascade');
            // Tambahkan foreign key untuk jenis_dokumen_id jika ada tabel master untuk jenis dokumen
            // $table->foreign('jenis_dokumen_id')->references('id')->on('mst_jenis_dokumen')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelatih_dokumen');
    }
};
