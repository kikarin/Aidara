<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cabor_kategori', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabor_id');
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P', 'C'])->default('C')->comment('L=Laki-laki, P=Perempuan, C=Campuran');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabor_kategori');
    }
};
