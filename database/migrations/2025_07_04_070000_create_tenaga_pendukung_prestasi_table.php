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
        Schema::create('tenaga_pendukung_prestasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->string('nama_event')->nullable();
            $table->unsignedBigInteger('tingkat_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('peringkat')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenaga_pendukung_prestasi');
    }
};
