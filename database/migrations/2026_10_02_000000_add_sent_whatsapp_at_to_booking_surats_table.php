<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_surats', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_surats', 'sent_whatsapp_at')) {
                $table->timestamp('sent_whatsapp_at')->nullable()->after('sent_email_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_surats', function (Blueprint $table) {
            if (Schema::hasColumn('booking_surats', 'sent_whatsapp_at')) {
                $table->dropColumn('sent_whatsapp_at');
            }
        });
    }
};
