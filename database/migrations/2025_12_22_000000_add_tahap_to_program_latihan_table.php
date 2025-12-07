<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('program_latihan', function (Blueprint $table) {
            $table->enum('tahap', ['persiapan umum', 'persiapan khusus', 'prapertandingan', 'pertandingan', 'transisi'])
                ->nullable()
                ->after('periode_selesai');
            $table->dropColumn('jenis_periode');
        });
    }

    public function down(): void
    {
        Schema::table('program_latihan', function (Blueprint $table) {
            $table->enum('jenis_periode', ['harian', 'mingguan', 'bulanan', 'tahunan'])
                ->nullable()
                ->after('periode_selesai');
            $table->dropColumn('tahap');
        });
    }
};

