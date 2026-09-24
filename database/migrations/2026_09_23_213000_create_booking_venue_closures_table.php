<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('booking_venue_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('booking_venues')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('booking_areas')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['venue_id', 'starts_at', 'ends_at']);
            $table->index(['area_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_venue_closures');
    }
};
