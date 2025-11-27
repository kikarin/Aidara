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
        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->date('tanggal_lahir')->nullable();
                    $table->string('no_hp', 50)->nullable();

                    $table->boolean('is_active')->default(true)->index();
                    $table->string('file')->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
