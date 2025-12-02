<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('mst_kategori_atlet', 'mst_kategori_peserta');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('mst_kategori_peserta', 'mst_kategori_atlet');
    }
};
