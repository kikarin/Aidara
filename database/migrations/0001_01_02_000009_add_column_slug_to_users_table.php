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
            if (! Schema::hasColumn('users', 'slug')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->text('slug')->nullable();
                    $table->text('deskripsi')->nullable();
                    $table->string('verification_token')->nullable();
                    $table->boolean('is_verifikasi')->default(true);
                    $table->string('reset_password_token')->nullable()->unique();
                    $table->unsignedBigInteger('current_role_id')->nullable();
                    $table->foreign('current_role_id')
                        ->references('id')
                        ->on('roles');
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
