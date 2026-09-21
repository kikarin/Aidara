<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // Ubah unique constraint untuk cabor_kategori_atlet
        // Dari: ['cabor_id', 'atlet_id'] (atlet hanya bisa di satu kategori per cabor)
        // Ke: ['cabor_id', 'cabor_kategori_id', 'atlet_id'] (atlet bisa di beberapa kategori dalam satu cabor)
        
        $indexes = DB::select("SHOW INDEX FROM cabor_kategori_atlet WHERE Key_name = ?", ['cabor_atlet_unique']);
        
        if (count($indexes) > 0) {
            // Drop foreign key yang mungkin menggunakan index ini
            // Foreign key cabor_id dan atlet_id mungkin menggunakan index cabor_atlet_unique
            // Kita perlu drop foreign key terlebih dahulu, kemudian drop index, kemudian re-create foreign key
            
            // Cek dan drop foreign key dengan nama yang mungkin
            $possibleForeignKeys = [
                'cabor_kategori_atlet_cabor_id_foreign',
                'cabor_kategori_atlet_atlet_id_foreign',
            ];
            
            foreach ($possibleForeignKeys as $fkName) {
                $this->dropForeignKeyIfExists('cabor_kategori_atlet', $fkName);
            }
            
            // Drop index
            DB::statement('ALTER TABLE cabor_kategori_atlet DROP INDEX cabor_atlet_unique');
            
            // Re-create foreign key (mereka tidak memerlukan unique index, hanya index biasa yang akan dibuat otomatis)
            Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
                $table->foreign('cabor_id')->references('id')->on('cabor')->onDelete('cascade');
                $table->foreign('atlet_id')->references('id')->on('atlets')->onDelete('cascade');
            });
        }
        
        // Buat constraint baru yang mengizinkan atlet di beberapa kategori dalam satu cabor
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->unique(['cabor_id', 'cabor_kategori_id', 'atlet_id'], 'cabor_kategori_atlet_unique');
        });
    }

    public function down(): void
    {
        $indexes = DB::select("SHOW INDEX FROM cabor_kategori_atlet WHERE Key_name = ?", ['cabor_kategori_atlet_unique']);
        
        if (count($indexes) > 0) {
            DB::statement('ALTER TABLE cabor_kategori_atlet DROP INDEX cabor_kategori_atlet_unique');
        }
        
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->unique(['cabor_id', 'atlet_id'], 'cabor_atlet_unique');
        });
    }

    /**
     * Helper: Drop foreign key if exists
     */
    private function dropForeignKeyIfExists(string $table, string $keyName): void
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = ?
        ", [$table, $keyName]);

        if (count($foreignKeys) > 0) {
            Schema::table($table, function (Blueprint $table) use ($keyName) {
                $table->dropForeign($keyName);
            });
        }
    }
};

