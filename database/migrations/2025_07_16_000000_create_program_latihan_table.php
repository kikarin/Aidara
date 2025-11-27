<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('program_latihan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabor_id');
            $table->string('nama_program');
            $table->unsignedBigInteger('cabor_kategori_id');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_latihan');
    }
};
