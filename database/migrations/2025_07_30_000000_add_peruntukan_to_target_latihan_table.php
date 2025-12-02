<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('target_latihan', function (Blueprint $table) {
            $table->enum('peruntukan', ['atlet', 'pelatih', 'tenaga-pendukung'])->nullable()->after('jenis_target');
        });
    }

    public function down(): void
    {
        Schema::table('target_latihan', function (Blueprint $table) {
            $table->dropColumn('peruntukan');
        });
    }
};
