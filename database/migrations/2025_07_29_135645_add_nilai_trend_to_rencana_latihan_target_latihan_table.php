<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rencana_latihan_target_latihan', function (Blueprint $table) {
            $table->decimal('nilai', 10, 2)->nullable()->after('target_latihan_id');
            $table->enum('trend', ['naik', 'stabil', 'turun'])->default('stabil')->after('nilai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rencana_latihan_target_latihan', function (Blueprint $table) {
            $table->dropColumn(['nilai', 'trend']);
        });
    }
};
