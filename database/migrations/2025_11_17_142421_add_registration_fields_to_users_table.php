<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'registration_status')) {
                $table->enum('registration_status', ['pending', 'approved', 'rejected'])->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'registration_rejected_reason')) {
                $table->text('registration_rejected_reason')->nullable()->after('registration_status');
            }
            if (!Schema::hasColumn('users', 'peserta_type')) {
                $table->enum('peserta_type', ['atlet', 'pelatih', 'tenaga_pendukung'])->nullable()->after('registration_rejected_reason');
            }
            if (!Schema::hasColumn('users', 'peserta_id')) {
                $table->unsignedBigInteger('peserta_id')->nullable()->after('peserta_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'peserta_id')) {
                $table->dropColumn('peserta_id');
            }
            if (Schema::hasColumn('users', 'peserta_type')) {
                $table->dropColumn('peserta_type');
            }
            if (Schema::hasColumn('users', 'registration_rejected_reason')) {
                $table->dropColumn('registration_rejected_reason');
            }
            if (Schema::hasColumn('users', 'registration_status')) {
                $table->dropColumn('registration_status');
            }
        });
    }
};
