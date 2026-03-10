<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('atlets', function (Blueprint $table) {
            $table->string('disabilitas', 255)->nullable()->after('email');
            $table->string('klasifikasi', 255)->nullable()->after('disabilitas');
            $table->string('iq', 50)->nullable()->after('klasifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('atlets', function (Blueprint $table) {
            $table->dropColumn(['disabilitas', 'klasifikasi', 'iq']);
        });
    }
};

