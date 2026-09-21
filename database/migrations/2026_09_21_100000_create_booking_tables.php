<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('booking_venues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('booking_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('booking_venues')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->boolean('is_tentative')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique(['venue_id', 'code']);
        });

        Schema::create('booking_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('booking_venues')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('booking_areas')->nullOnDelete();
            $table->string('code', 96)->nullable();
            $table->string('uraian');
            $table->string('satuan', 32);
            $table->unsignedBigInteger('tarif_pemerintah')->nullable();
            $table->unsignedBigInteger('tarif_non_pemerintah')->nullable();
            $table->string('time_slot', 32)->nullable();
            $table->string('audience_type', 32)->nullable();
            $table->string('day_type', 32)->nullable();
            $table->string('vehicle_class', 64)->nullable();
            $table->string('event_level', 64)->nullable();
            $table->string('category', 64)->nullable()->comment('olahraga|non_olahraga|sewa_lahan|ruang');
            $table->date('effective_from')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['venue_id', 'satuan', 'is_active']);
            $table->index(['area_id', 'is_active']);
        });

        Schema::create('booking_addons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('harga')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('booking_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->nullable()->constrained('booking_venues')->cascadeOnDelete();
            $table->string('key', 96);
            $table->json('value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique(['venue_id', 'key']);
        });

        Schema::create('booking_priority_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('priority_order');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('booking_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('booking_penyewa_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nik', 32)->nullable();
            $table->string('no_hp', 32)->nullable();
            $table->text('alamat')->nullable();
            $table->string('instansi')->nullable();
            $table->string('kategori_default', 32)->nullable()->comment('pemerintah|non_pemerintah');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique('user_id');
        });

        Schema::create('booking_penyewa_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyewa_profile_id')->constrained('booking_penyewa_profiles')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('booking_document_types')->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['penyewa_profile_id', 'document_type_id'], 'booking_penyewa_docs_profile_type_idx');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('penyewa_profile_id')->nullable()->constrained('booking_penyewa_profiles')->nullOnDelete();
            $table->foreignId('venue_id')->constrained('booking_venues')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('booking_areas')->nullOnDelete();
            $table->foreignId('priority_rule_id')->nullable()->constrained('booking_priority_rules')->nullOnDelete();
            $table->string('kategori_tarif', 32)->comment('pemerintah|non_pemerintah');
            $table->string('tujuan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status', 40)->default('draft');
            $table->string('priority_flag', 16)->default('normal')->comment('unggul|normal|rendah');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('buffer_before_days')->nullable();
            $table->unsignedSmallInteger('buffer_after_days')->nullable();
            $table->decimal('luas_m2', 12, 2)->nullable();
            $table->unsignedInteger('qty')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('addon_total')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['venue_id', 'area_id', 'starts_at', 'ends_at'], 'bookings_venue_area_time_idx');
            $table->index(['status', 'starts_at'], 'bookings_status_starts_idx');
            $table->index(['user_id', 'status'], 'bookings_user_status_idx');
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('tarif_id')->nullable()->constrained('booking_tarifs')->nullOnDelete();
            $table->string('uraian');
            $table->string('satuan', 32);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('luas_m2', 12, 2)->nullable();
            $table->unsignedInteger('duration_value')->nullable()->comment('jam/hari/blok sesuai satuan');
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('line_total')->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index('booking_id');
        });

        Schema::create('booking_addon_selected', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('addon_id')->nullable()->constrained('booking_addons')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('line_total')->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index('booking_id');
        });

        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('gateway', 32)->default('manual');
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status', 32)->default('pending');
            $table->string('bank')->nullable();
            $table->string('rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('bukti_path')->nullable();
            $table->string('gateway_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['booking_id', 'status']);
        });

        Schema::create('booking_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('play_elapsed_minutes')->nullable();
            $table->dateTime('play_started_at')->nullable();
            $table->string('decision', 40)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['booking_id', 'type']);
        });

        Schema::create('booking_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->json('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
        Schema::dropIfExists('booking_status_logs');
        Schema::dropIfExists('booking_incidents');
        Schema::dropIfExists('booking_payments');
        Schema::dropIfExists('booking_addon_selected');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('booking_penyewa_documents');
        Schema::dropIfExists('booking_penyewa_profiles');
        Schema::dropIfExists('booking_document_types');
        Schema::dropIfExists('booking_priority_rules');
        Schema::dropIfExists('booking_rules');
        Schema::dropIfExists('booking_addons');
        Schema::dropIfExists('booking_tarifs');
        Schema::dropIfExists('booking_areas');
        Schema::dropIfExists('booking_venues');
    }
};
