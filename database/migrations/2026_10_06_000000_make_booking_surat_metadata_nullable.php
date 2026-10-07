<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('booking_surats', function (Blueprint $table) {
            $table->string('nomor_surat', 150)->nullable()->change();
            $table->string('perihal', 200)->nullable()->change();
            $table->text('isi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_surats', function (Blueprint $table) {
            $table->string('nomor_surat', 150)->nullable(false)->change();
            $table->string('perihal', 200)->nullable(false)->change();
            $table->text('isi')->nullable(false)->change();
        });
    }
};
