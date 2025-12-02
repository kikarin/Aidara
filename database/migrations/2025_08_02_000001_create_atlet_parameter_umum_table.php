<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('atlet_parameter_umum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atlet_id');
            $table->unsignedBigInteger('mst_parameter_id');
            $table->string('nilai')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
            $table->foreign('mst_parameter_id')->references('id')->on('mst_parameter')->onDelete('cascade');
            $table->unique(['atlet_id', 'mst_parameter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlet_parameter_umum');
    }
};
