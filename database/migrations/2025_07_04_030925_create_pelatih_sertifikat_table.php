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
        Schema::create('pelatih_sertifikat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelatih_id');
            $table->string('nama_sertifikat')->nullable();
            $table->string('penyelenggara')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->string('file', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pelatih_id')->references('id')->on('pelatihs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelatih_sertifikat');
    }
};
