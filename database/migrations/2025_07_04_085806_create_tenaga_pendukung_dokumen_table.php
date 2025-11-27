<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tenaga_pendukung_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->unsignedBigInteger('jenis_dokumen_id')->nullable();
            $table->string('nomor')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
            // $table->foreign('jenis_dokumen_id')->references('id')->on('mst_jenis_dokumen')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenaga_pendukung_dokumen');
    }
};
