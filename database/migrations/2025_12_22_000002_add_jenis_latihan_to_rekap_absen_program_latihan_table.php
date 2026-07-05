<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('rekap_absen_program_latihan', function (Blueprint $table) {
            $table->enum('jenis_latihan', [
                'latihan_fisik',
                'latihan_strategi',
                'latihan_teknik',
                'latihan_mental',
                'latihan_pemulihan'
            ])->nullable()->after('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_absen_program_latihan', function (Blueprint $table) {
            $table->dropColumn('jenis_latihan');
        });
    }
};

