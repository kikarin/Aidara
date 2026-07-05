<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('program_latihan_absen_atlet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_latihan_id');
            $table->unsignedBigInteger('rekap_absen_program_latihan_id')->nullable();
            $table->unsignedBigInteger('atlet_id');
            $table->date('tanggal');
            $table->string('status', 20)->default('hadir');
            $table->time('waktu_foto')->nullable();
            $table->text('lokasi')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('program_latihan_id', 'pl_absen_atlet_pl_fk')
                ->references('id')->on('program_latihan')->onDelete('cascade');
            $table->foreign('rekap_absen_program_latihan_id', 'pl_absen_atlet_rekap_fk')
                ->references('id')->on('rekap_absen_program_latihan')->nullOnDelete();
            $table->foreign('atlet_id', 'pl_absen_atlet_atlet_fk')
                ->references('id')->on('atlets')->onDelete('cascade');
            $table->unique(['program_latihan_id', 'atlet_id', 'tanggal'], 'pl_absen_atlet_unique');
            $table->index(['program_latihan_id', 'tanggal'], 'pl_absen_atlet_pl_tgl_idx');
            $table->index(['atlet_id', 'tanggal'], 'pl_absen_atlet_atlet_tgl_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_latihan_absen_atlet');
    }
};
