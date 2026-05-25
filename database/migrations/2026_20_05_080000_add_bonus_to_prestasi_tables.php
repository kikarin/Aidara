<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
            if (!Schema::hasColumn('atlet_prestasi', 'bonus')) {
                Schema::table('atlet_prestasi', function (Blueprint $table) {
                    $table->decimal('bonus', 15, 2)->nullable()->default(0)->after('keterangan');
                });
            }
        if (!Schema::hasColumn('pelatih_prestasi', 'bonus')) {
            Schema::table('pelatih_prestasi', function (Blueprint $table) {
                $table->decimal('bonus', 15, 2)->nullable()->default(0)->after('keterangan');
            });
        }
        if (!Schema::hasColumn('tenaga_pendukung_prestasi', 'bonus')) {
            Schema::table('tenaga_pendukung_prestasi', function (Blueprint $table) {
                $table->decimal('bonus', 15, 2)->nullable()->default(0)->after('keterangan');
            });
        }
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
