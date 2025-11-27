<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('atlet_kategori_atlet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atlet_id');
            $table->unsignedBigInteger('mst_kategori_atlet_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
            $table->foreign('mst_kategori_atlet_id')->references('id')->on('mst_kategori_atlet')->onDelete('cascade');
            $table->unique(['atlet_id', 'mst_kategori_atlet_id'], 'unique_atlet_kategori_atlet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlet_kategori_atlet');
    }
};
