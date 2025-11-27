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
        Schema::create('event', function (Blueprint $table) {
            $table->id();
            $table->string('nama_event');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('kategori_event_id')->nullable(); // cabor_kategori_id
            $table->unsignedBigInteger('tingkat_event_id')->nullable(); // mst_tingkat_id
            $table->string('lokasi')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['draft', 'publish', 'selesai', 'dibatalkan'])->default('draft');

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('kategori_event_id')->references('id')->on('cabor_kategori')->onDelete('set null');
            $table->foreign('tingkat_event_id')->references('id')->on('mst_tingkat')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};

