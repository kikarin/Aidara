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
        Schema::create('atlet_prestasi_beregu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestasi_group_id')->comment('ID dari atlet_prestasi yang menjadi group leader');
            $table->unsignedBigInteger('atlet_id');
            $table->unsignedBigInteger('atlet_prestasi_id')->comment('ID dari atlet_prestasi untuk atlet ini');
            $table->timestamps();

            $table->foreign('prestasi_group_id')->references('id')->on('atlet_prestasi')->onDelete('cascade');
            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
            $table->foreign('atlet_prestasi_id')->references('id')->on('atlet_prestasi')->onDelete('cascade');
            
            $table->unique(['prestasi_group_id', 'atlet_id'], 'prestasi_group_atlet_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atlet_prestasi_beregu');
    }
};

