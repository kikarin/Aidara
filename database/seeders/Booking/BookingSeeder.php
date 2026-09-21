<?php

namespace Database\Seeders\Booking;

use Illuminate\Database\Seeder;

/**
 * Orchestrator seeder E-Booking (Step 2).
 *
 * php artisan db:seed --class=Database\\Seeders\\Booking\\BookingSeeder
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BookingVenueSeeder::class,
            BookingTarifOlahragaSeeder::class,
            BookingSewaLahanSeeder::class,
            BookingRulesSeeder::class,
            BookingMetaSeeder::class,
            BookingRoleSeeder::class,
            BookingUserSeeder::class,
        ]);

        $this->command?->info('E-Booking master data seeded.');
    }
}
