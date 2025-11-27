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
        Schema::create('users_menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200)->nullable();
            $table->string('kode', 200)->nullable();
            $table->text('icon')->nullable();
            $table->unsignedBigInteger('rel')->nullable();
            $table->unsignedBigInteger('urutan')->nullable();
            $table->text('url')->nullable();

            $table->unsignedBigInteger('permission_id')->nullable();
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_menus');
    }
};
