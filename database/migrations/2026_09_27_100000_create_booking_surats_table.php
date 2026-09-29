<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('jenis', 32)->comment('undangan_meeting|balasan_persetujuan|balasan_penolakan');
            $table->string('nomor_surat', 150);
            $table->string('perihal', 200);
            $table->text('isi');
            $table->dateTime('meeting_at')->nullable();
            $table->string('meeting_place', 200)->nullable();
            $table->json('dokumen')->nullable()->comment('Daftar nama dokumen yang harus disiapkan penyewa');
            $table->string('penandatangan_nama', 150)->nullable();
            $table->string('penandatangan_jabatan', 150)->nullable();
            $table->string('file_path');
            $table->timestamp('sent_email_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'jenis'], 'booking_surats_booking_jenis_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_surats');
    }
};
