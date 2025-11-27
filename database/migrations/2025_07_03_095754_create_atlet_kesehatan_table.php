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
        Schema::create('atlet_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atlet_id');
            $table->string('golongan_darah')->nullable();
            $table->string('tinggi_badan')->nullable();
            $table->string('berat_badan')->nullable();
            $table->string('penglihatan')->nullable();
            $table->string('pendengaran')->nullable();
            $table->text('riwayat_penyakit')->nullable();
            $table->text('alergi')->nullable();
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
        Schema::dropIfExists('atlet_kesehatan');
    }
};
