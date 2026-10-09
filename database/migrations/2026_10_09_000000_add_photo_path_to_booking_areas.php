<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('booking_areas', 'photo_path')) {
            return;
        }

        Schema::table('booking_areas', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('booking_areas', 'photo_path')) {
            return;
        }

        Schema::table('booking_areas', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
