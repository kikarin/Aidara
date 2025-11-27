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
        Schema::create('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabor_id');
            $table->unsignedBigInteger('cabor_kategori_id');
            $table->unsignedBigInteger('tenaga_pendukung_id');
            $table->unsignedBigInteger('jenis_tenaga_pendukung_id');
            $table->tinyInteger('is_active')->default(1)->comment('1=Aktif, 0=Nonaktif');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Foreign keys
            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
            $table->foreign('tenaga_pendukung_id')->references('id')->on('tenaga_pendukungs')->onDelete('cascade');
            $table->foreign('jenis_tenaga_pendukung_id', 'fk_jenis_tp_id')
                ->references('id')->on('mst_jenis_tenaga_pendukung')->onDelete('cascade');

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['cabor_kategori_id', 'tenaga_pendukung_id'], 'cabor_kategori_tenaga_pendukung_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabor_kategori_tenaga_pendukung');
    }
};
