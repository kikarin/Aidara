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
        Schema::create('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabor_id');
            $table->unsignedBigInteger('cabor_kategori_id');
            $table->unsignedBigInteger('pelatih_id');
            $table->unsignedBigInteger('jenis_pelatih_id');
            $table->tinyInteger('is_active')->default(1)->comment('1=Aktif, 0=Nonaktif');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Foreign keys
            $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
            $table->foreign('pelatih_id')->references('id')->on('pelatihs')->onDelete('cascade');
            $table->foreign('jenis_pelatih_id')->references('id')->on('mst_jenis_pelatih')->onDelete('cascade');

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['cabor_kategori_id', 'pelatih_id'], 'cabor_kategori_pelatih_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabor_kategori_pelatih');
    }
};
