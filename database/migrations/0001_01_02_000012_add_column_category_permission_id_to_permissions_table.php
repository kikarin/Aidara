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
        if (Schema::hasTable('permissions')) {
            if (! Schema::hasColumn('permissions', 'category_permission_id')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->unsignedBigInteger('category_permission_id')->nullable();
                    $table->foreign('category_permission_id')
                        ->references('id')
                        ->on('category_permissions');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            //
        });
    }
};
