<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_area_selected', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('booking_areas')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'area_id']);
            $table->index('area_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['venue_id', 'starts_at', 'ends_at'], 'bookings_venue_time_idx');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropIndex('bookings_venue_area_time_idx');
            $table->dropColumn('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_venue_time_idx');
            $table->foreignId('area_id')->nullable()->constrained('booking_areas')->nullOnDelete();
            $table->index(['venue_id', 'area_id', 'starts_at', 'ends_at'], 'bookings_venue_area_time_idx');
        });

        Schema::dropIfExists('booking_area_selected');
    }
};
