<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstDesa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst_desa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kecamatan');
            $table->string('nama');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamps();

            $table->foreign('id_kecamatan')->references('id')->on('mst_kecamatan');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mst_desa');
    }
}
