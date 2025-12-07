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
        Schema::create('pemeriksaan_khusus_aspek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pemeriksaan_khusus_id');
            $table->string('nama', 200);
            $table->integer('urutan')->default(0);
            $table->unsignedBigInteger('mst_template_aspek_id')->nullable()->comment('Untuk tracking source dari template');
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('pemeriksaan_khusus_id', 'fk_pk_aspek_pk_id')->references('id')->on('pemeriksaan_khusus')->onDelete('cascade');
            $table->foreign('mst_template_aspek_id', 'fk_pk_aspek_template')->references('id')->on('mst_template_pemeriksaan_khusus_aspek')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan_khusus_aspek');
    }
};

