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
        Schema::create('tenaga_pendukungs', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 30)->unique();
            $table->string('nama', 200);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->date('tanggal_bergabung')->nullable();
            $table->text('alamat')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->unsignedBigInteger('kelurahan_id')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('foto', 255)->nullable();
            $table->unsignedBigInteger('users_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('users_id')->references('id')->on('users')->onDelete('set null');
            // Foreign keys
            $table->foreign('kecamatan_id')->references('id')->on('mst_kecamatan')->onDelete('set null');
            $table->foreign('kelurahan_id')->references('id')->on('mst_desa')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenaga_pendukungs');
    }
};
