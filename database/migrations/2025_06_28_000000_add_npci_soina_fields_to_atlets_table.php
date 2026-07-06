<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('atlets', function (Blueprint $table) {
            if (!Schema::hasColumn('atlets', 'disabilitas')) {
                $table->string('disabilitas', 255)->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('atlets', 'klasifikasi')) {
                $table->string('klasifikasi', 255)->nullable()->after('disabilitas');
            }
            
            if (!Schema::hasColumn('atlets', 'iq')) {
                $table->string('iq', 50)->nullable()->after('klasifikasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('atlets', function (Blueprint $table) {
            $table->dropColumn(['disabilitas', 'klasifikasi', 'iq']);
        });
    }
};