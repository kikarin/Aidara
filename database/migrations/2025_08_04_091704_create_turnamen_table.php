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
        Schema::create('turnamen', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('cabor_kategori_id')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedBigInteger('tingkat_id')->nullable();
            $table->string('lokasi')->nullable();
            $table->unsignedBigInteger('juara_id')->nullable();
            $table->text('hasil')->nullable();
            $table->text('evaluasi')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('set null');
            $table->foreign('tingkat_id')->references('id')->on('mst_tingkat')->onDelete('set null');
            $table->foreign('juara_id')->references('id')->on('mst_juara')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnamen');
    }
};
