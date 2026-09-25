<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('booking_addon_venue')) {
            Schema::create('booking_addon_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('addon_id')->constrained('booking_addons')->cascadeOnDelete();
                $table->foreignId('venue_id')->constrained('booking_venues')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['addon_id', 'venue_id']);
            });
        }

        // Backfill: pasang semua layanan yang ada ke semua venue agar tampilan tidak berubah.
        $now = now();
        $addonIds = DB::table('booking_addons')->pluck('id');
        $venueIds = DB::table('booking_venues')->pluck('id');

        $rows = [];
        foreach ($venueIds as $venueId) {
            foreach ($addonIds as $addonId) {
                $rows[] = [
                    'addon_id' => $addonId,
                    'venue_id' => $venueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('booking_addon_venue')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_addon_venue');
    }
};
