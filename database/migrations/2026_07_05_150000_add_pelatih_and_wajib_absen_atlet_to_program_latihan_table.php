<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('program_latihan', function (Blueprint $table) {
            $table->unsignedBigInteger('pelatih_id')->nullable()->after('cabor_kategori_id');
            $table->boolean('wajib_absen_atlet')->default(false)->after('pelatih_id');

            $table->foreign('pelatih_id', 'pl_latihan_pelatih_fk')
                ->references('id')->on('pelatihs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_latihan', function (Blueprint $table) {
            $table->dropForeign('pl_latihan_pelatih_fk');
            $table->dropColumn(['pelatih_id', 'wajib_absen_atlet']);
        });
    }
};
