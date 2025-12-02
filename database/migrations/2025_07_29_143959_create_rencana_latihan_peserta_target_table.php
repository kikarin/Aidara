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
        Schema::create('rencana_latihan_peserta_target', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rencana_latihan_id');
            $table->unsignedBigInteger('target_latihan_id');
            $table->unsignedBigInteger('peserta_id');
            $table->string('peserta_type');
            $table->string('nilai')->nullable();
            $table->enum('trend', ['naik', 'stabil', 'turun'])->default('stabil');
            $table->timestamps();

            $table->foreign('rencana_latihan_id')->references('id')->on('rencana_latihan')->onDelete('cascade');
            $table->foreign('target_latihan_id')->references('id')->on('target_latihan')->onDelete('cascade');

            $table->unique(['rencana_latihan_id', 'target_latihan_id', 'peserta_id', 'peserta_type'], 'unique_peserta_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_latihan_peserta_target');
    }
};
