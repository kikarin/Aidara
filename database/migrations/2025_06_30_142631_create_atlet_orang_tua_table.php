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
        Schema::create('atlet_orang_tua', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atlet_id');
            $table->string('nama_ibu_kandung')->nullable();
            $table->string('tempat_lahir_ibu')->nullable();
            $table->date('tanggal_lahir_ibu')->nullable();
            $table->text('alamat_ibu')->nullable();
            $table->string('no_hp_ibu', 20)->nullable();
            $table->string('pekerjaan_ibu')->nullable();

            $table->string('nama_ayah_kandung')->nullable();
            $table->string('tempat_lahir_ayah')->nullable();
            $table->date('tanggal_lahir_ayah')->nullable();
            $table->text('alamat_ayah')->nullable();
            $table->string('no_hp_ayah', 20)->nullable();
            $table->string('pekerjaan_ayah')->nullable();

            $table->string('nama_wali')->nullable();
            $table->string('tempat_lahir_wali')->nullable();
            $table->date('tanggal_lahir_wali')->nullable();
            $table->text('alamat_wali')->nullable();
            $table->string('no_hp_wali', 20)->nullable();
            $table->string('pekerjaan_wali')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atlet_orang_tua');
    }
};
