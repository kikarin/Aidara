<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('cabor_kategori', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_peserta_id')->nullable()->after('jenis_kelamin');
            $table->foreign('kategori_peserta_id')
                ->references('id')
                ->on('mst_kategori_peserta')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('cabor_kategori', function (Blueprint $table) {
            $table->dropForeign(['kategori_peserta_id']);
            $table->dropColumn('kategori_peserta_id');
        });
    }
};

