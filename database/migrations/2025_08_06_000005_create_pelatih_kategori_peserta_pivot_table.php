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
        if (Schema::hasTable('pelatih_kategori_peserta')) {
            return;
        }

        Schema::create('pelatih_kategori_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelatih_id');
            $table->unsignedBigInteger('mst_kategori_peserta_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pelatih_id', 'pkp_p_id_fk')->references('id')->on('pelatihs')->onDelete('cascade');
            $table->foreign('mst_kategori_peserta_id', 'pkp_mkp_id_fk')->references('id')->on('mst_kategori_peserta')->onDelete('cascade');
            $table->unique(['pelatih_id', 'mst_kategori_peserta_id'], 'unique_pelatih_kategori_peserta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelatih_kategori_peserta');
    }
};
