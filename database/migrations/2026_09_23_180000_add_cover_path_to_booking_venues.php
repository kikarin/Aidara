<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('booking_venues', 'cover_path')) {
            return;
        }

        Schema::table('booking_venues', function (Blueprint $table) {
            $table->string('cover_path', 255)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('booking_venues', 'cover_path')) {
            return;
        }

        Schema::table('booking_venues', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }
};
