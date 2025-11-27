<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('mst_parameter', function (Blueprint $table) {
            $table->enum('kategori', ['kesehatan', 'khusus', 'umum'])->default('kesehatan')->after('satuan');
            $table->string('nilai_target')->nullable()->after('kategori');
            $table->enum('performa_arah', ['min', 'max'])->default('max')->after('nilai_target');
        });
    }

    public function down(): void
    {
        Schema::table('mst_parameter', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'nilai_target', 'performa_arah']);
        });
    }
};
