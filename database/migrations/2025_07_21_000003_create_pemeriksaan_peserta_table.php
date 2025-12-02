<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pemeriksaan_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pemeriksaan_id');
            $table->morphs('peserta');
            $table->unsignedBigInteger('ref_status_pemeriksaan_id')->nullable();
            $table->text('catatan_umum')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pemeriksaan_id')->references('id')->on('pemeriksaan')->onDelete('cascade');
            $table->foreign('ref_status_pemeriksaan_id')->references('id')->on('ref_status_pemeriksaan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_peserta');
    }
};
