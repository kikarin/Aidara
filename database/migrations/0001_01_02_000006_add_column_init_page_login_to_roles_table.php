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
            if (! Schema::hasColumn('roles', 'init_page_login')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->string('bg', 100)->nullable();
                    $table->string('init_page_login', 100)->nullable();
                    $table->boolean('is_vertical_menu')->default(true)->nullable();
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
