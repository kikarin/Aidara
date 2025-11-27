<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table already renamed
        if (Schema::hasTable('atlet_kategori_atlet') && !Schema::hasTable('atlet_kategori_peserta')) {
            Schema::rename('atlet_kategori_atlet', 'atlet_kategori_peserta');
        }

        // Check if column needs to be renamed
        if (Schema::hasTable('atlet_kategori_peserta') && Schema::hasColumn('atlet_kategori_peserta', 'mst_kategori_atlet_id')) {
            // Drop foreign key if exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'atlet_kategori_peserta' 
                AND COLUMN_NAME = 'mst_kategori_atlet_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($foreignKeys)) {
                $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE `atlet_kategori_peserta` DROP FOREIGN KEY `{$fkName}`");
            }

            Schema::table('atlet_kategori_peserta', function (Blueprint $table) {
                $table->renameColumn('mst_kategori_atlet_id', 'mst_kategori_peserta_id');
            });
        }

        // Add foreign key if not exists
        if (Schema::hasTable('atlet_kategori_peserta') && Schema::hasColumn('atlet_kategori_peserta', 'mst_kategori_peserta_id')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'atlet_kategori_peserta' 
                AND COLUMN_NAME = 'mst_kategori_peserta_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (empty($foreignKeys)) {
                Schema::table('atlet_kategori_peserta', function (Blueprint $table) {
                    $table->foreign('mst_kategori_peserta_id')->references('id')->on('mst_kategori_peserta')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key if exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'atlet_kategori_peserta' 
            AND COLUMN_NAME = 'mst_kategori_peserta_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($foreignKeys)) {
            $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE `atlet_kategori_peserta` DROP FOREIGN KEY `{$fkName}`");
        }

        Schema::table('atlet_kategori_peserta', function (Blueprint $table) {
            $table->renameColumn('mst_kategori_peserta_id', 'mst_kategori_atlet_id');
        });

        Schema::table('atlet_kategori_peserta', function (Blueprint $table) {
            $table->foreign('mst_kategori_atlet_id')->references('id')->on('mst_kategori_atlet')->onDelete('cascade');
        });

        Schema::rename('atlet_kategori_peserta', 'atlet_kategori_atlet');
    }
};
