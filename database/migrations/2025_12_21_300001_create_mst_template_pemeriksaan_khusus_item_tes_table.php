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
        Schema::create('mst_template_pemeriksaan_khusus_item_tes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mst_template_pemeriksaan_khusus_aspek_id');
            $table->string('nama', 200);
            $table->string('satuan', 50)->nullable();
            $table->string('target_laki_laki')->nullable();
            $table->string('target_perempuan')->nullable();
            $table->enum('performa_arah', ['max', 'min'])->default('max');
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('mst_template_pemeriksaan_khusus_aspek_id', 'fk_template_aspek')
                ->references('id')
                ->on('mst_template_pemeriksaan_khusus_aspek')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_template_pemeriksaan_khusus_item_tes');
    }
};

