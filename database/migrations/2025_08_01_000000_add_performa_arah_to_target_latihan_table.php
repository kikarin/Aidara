<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('target_latihan', function (Blueprint $table) {
            $table->enum('performa_arah', ['min', 'max'])->default('max')->after('nilai_target')->comment('min = semakin kecil nilai semakin baik, max = semakin besar nilai semakin baik');
        });
    }

    public function down(): void
    {
        Schema::table('target_latihan', function (Blueprint $table) {
            $table->dropColumn('performa_arah');
        });
    }
};
