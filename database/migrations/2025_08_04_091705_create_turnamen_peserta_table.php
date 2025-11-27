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
        Schema::create('turnamen_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turnamen_id');
            $table->morphs('peserta');
            $table->timestamps();

            $table->foreign('turnamen_id')->references('id')->on('turnamen')->onDelete('cascade');
            $table->unique(['turnamen_id', 'peserta_type', 'peserta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnamen_peserta');
    }
};
