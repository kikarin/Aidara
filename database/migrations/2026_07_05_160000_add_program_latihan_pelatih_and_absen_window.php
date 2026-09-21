<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('program_latihan', function (Blueprint $table) {
            $table->string('mode_pelatih', 20)->default('single')->after('cabor_kategori_id');
            $table->time('absen_jam_mulai')->nullable()->after('wajib_absen_atlet');
            $table->time('absen_jam_selesai')->nullable()->after('absen_jam_mulai');
        });

        Schema::create('program_latihan_pelatih', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_latihan_id');
            $table->unsignedBigInteger('pelatih_id');
            $table->timestamps();

            $table->foreign('program_latihan_id', 'pl_pelatih_pl_fk')
                ->references('id')->on('program_latihan')->onDelete('cascade');
            $table->foreign('pelatih_id', 'pl_pelatih_pel_fk')
                ->references('id')->on('pelatihs')->onDelete('cascade');
            $table->unique(['program_latihan_id', 'pelatih_id'], 'pl_pelatih_unique');
        });

        if (Schema::hasColumn('program_latihan', 'pelatih_id')) {
            $rows = DB::table('program_latihan')->whereNotNull('pelatih_id')->orderBy('id')->get();
            foreach ($rows as $row) {
                DB::table('program_latihan_pelatih')->insertOrIgnore([
                    'program_latihan_id' => $row->id,
                    'pelatih_id' => $row->pelatih_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_latihan_pelatih');

        Schema::table('program_latihan', function (Blueprint $table) {
            $table->dropColumn(['mode_pelatih', 'absen_jam_mulai', 'absen_jam_selesai']);
        });
    }
};
