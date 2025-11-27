<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // Update target latihan jenis kelompok agar peruntukan menjadi null
        DB::table('target_latihan')
            ->where('jenis_target', 'kelompok')
            ->update(['peruntukan' => null]);
    }

    public function down(): void
    {
        // Rollback: set peruntukan ke 'atlet' untuk target kelompok
        DB::table('target_latihan')
            ->where('jenis_target', 'kelompok')
            ->whereNull('peruntukan')
            ->update(['peruntukan' => 'atlet']);
    }
};
