<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'surat_permohonan_path')) {
                $table->string('surat_permohonan_path')->nullable()->after('admin_notes');
            }
            if (! Schema::hasColumn('bookings', 'surat_permohonan_name')) {
                $table->string('surat_permohonan_name')->nullable()->after('surat_permohonan_path');
            }
            if (! Schema::hasColumn('bookings', 'submitted_surat_permohonan_at')) {
                $table->timestamp('submitted_surat_permohonan_at')->nullable()->after('surat_permohonan_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['surat_permohonan_path', 'surat_permohonan_name', 'submitted_surat_permohonan_at'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
