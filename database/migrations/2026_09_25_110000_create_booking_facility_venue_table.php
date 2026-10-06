<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('booking_facility_venue')) {
            return;
        }

        Schema::create('booking_facility_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('booking_venues')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('booking_facilities')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['venue_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_facility_venue');
    }
};
