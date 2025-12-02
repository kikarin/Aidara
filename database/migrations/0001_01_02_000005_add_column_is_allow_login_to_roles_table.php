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
        if (Schema::hasTable('roles')) {
            if (! Schema::hasColumn('roles', 'is_allow_login')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->boolean('is_allow_login')->default(true)->index();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->unsignedBigInteger('updated_by')->nullable();
                    $table->unsignedBigInteger('deleted_by')->nullable();
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            //
        });
    }
};
