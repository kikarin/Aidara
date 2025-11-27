<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tenaga_pendukung_sertifikat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->string('nama_sertifikat')->nullable();
            $table->string('penyelenggara')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->string('file', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenaga_pendukung_sertifikat');
    }
};
