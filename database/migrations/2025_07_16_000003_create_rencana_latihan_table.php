<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('rencana_latihan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_latihan_id');
            $table->date('tanggal');
            $table->string('lokasi_latihan');
            $table->text('materi');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('program_latihan_id')->references('id')->on('program_latihan')->onDelete('cascade');
        });

        // Pivot: rencana_latihan_target_latihan
        Schema::create('rencana_latihan_target_latihan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rencana_latihan_id');
            $table->unsignedBigInteger('target_latihan_id');
            $table->foreign('rencana_latihan_id')->references('id')->on('rencana_latihan')->onDelete('cascade');
            $table->foreign('target_latihan_id')->references('id')->on('target_latihan')->onDelete('cascade');
        });

        // Pivot: rencana_latihan_atlet
        Schema::create('rencana_latihan_atlet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rencana_latihan_id');
            $table->unsignedBigInteger('atlet_id');
            $table->string('kehadiran')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreign('rencana_latihan_id')->references('id')->on('rencana_latihan')->onDelete('cascade');
            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
        });

        // Pivot: rencana_latihan_pelatih
        Schema::create('rencana_latihan_pelatih', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rencana_latihan_id');
            $table->unsignedBigInteger('pelatih_id');
            $table->string('kehadiran')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreign('rencana_latihan_id')->references('id')->on('rencana_latihan')->onDelete('cascade');
            $table->foreign('pelatih_id')->references('id')->on('pelatihs')->onDelete('cascade');
        });

        // Pivot: rencana_latihan_tenaga_pendukung
        Schema::create('rencana_latihan_tenaga_pendukung', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rencana_latihan_id');
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->string('kehadiran')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreign('rencana_latihan_id')->references('id')->on('rencana_latihan')->onDelete('cascade');
            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_latihan_tenaga_pendukung');
        Schema::dropIfExists('rencana_latihan_pelatih');
        Schema::dropIfExists('rencana_latihan_atlet');
        Schema::dropIfExists('rencana_latihan_target_latihan');
        Schema::dropIfExists('rencana_latihan');
    }
};
