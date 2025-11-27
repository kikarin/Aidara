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
        Schema::create('pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabor_id');
            $table->unsignedBigInteger('cabor_kategori_id');
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->string('nama_pemeriksaan', 200);
            $table->date('tanggal_pemeriksaan');
            $table->enum('status', ['belum', 'sebagian', 'selesai'])->default('belum');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan');
    }
};
