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
        if (!Schema::hasTable('peserta_registrations')) {
            Schema::create('peserta_registrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('peserta_type', ['atlet', 'pelatih', 'tenaga_pendukung']);
                $table->tinyInteger('step_current')->default(1)->comment('1-5: step saat ini');
                $table->json('data_json')->nullable()->comment('Data sementara per step');
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                $table->text('rejected_reason')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_registrations');
    }
};
