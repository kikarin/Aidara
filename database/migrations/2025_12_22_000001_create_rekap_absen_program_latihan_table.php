<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('rekap_absen_program_latihan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_latihan_id');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('program_latihan_id')->references('id')->on('program_latihan')->onDelete('cascade');
            $table->unique(['program_latihan_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_absen_program_latihan');
    }
};

