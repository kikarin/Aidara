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
        Schema::create('identity', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->text('value')->nullable();
            $table->string('type_file')->nullable();
            $table->unsignedBigInteger('sequence')->nullable();
            $table->unsignedBigInteger('category_identity_id')->nullable();
            $table->foreign('category_identity_id')
                ->references('id')
                ->on('category_identity');
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
        Schema::dropIfExists('identity');
    }
};
