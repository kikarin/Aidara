<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('rencana_latihan_atlet', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('keterangan');
        });

        Schema::table('rencana_latihan_pelatih', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('keterangan');
        });

        Schema::table('rencana_latihan_tenaga_pendukung', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('rencana_latihan_atlet', function (Blueprint $table) {
            $table->dropColumn('foto');
        });

        Schema::table('rencana_latihan_pelatih', function (Blueprint $table) {
            $table->dropColumn('foto');
        });

        Schema::table('rencana_latihan_tenaga_pendukung', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
