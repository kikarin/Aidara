<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Add bonus to atlet_prestasi
        Schema::table('atlet_prestasi', function (Blueprint $table) {
            $table->decimal('bonus', 15, 2)->nullable()->after('keterangan')->default(0);
        });

        // Add bonus to pelatih_prestasi
        Schema::table('pelatih_prestasi', function (Blueprint $table) {
            $table->decimal('bonus', 15, 2)->nullable()->after('keterangan')->default(0);
        });

        // Add bonus to tenaga_pendukung_prestasi
        Schema::table('tenaga_pendukung_prestasi', function (Blueprint $table) {
            $table->decimal('bonus', 15, 2)->nullable()->after('keterangan')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('atlet_prestasi', function (Blueprint $table) {
            $table->dropColumn('bonus');
        });

        Schema::table('pelatih_prestasi', function (Blueprint $table) {
            $table->dropColumn('bonus');
        });

        Schema::table('tenaga_pendukung_prestasi', function (Blueprint $table) {
            $table->dropColumn('bonus');
        });
    }
};

