<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // =====================================================
        // PART 1: Tambah kategori_peserta_id ke tabel cabor
        // =====================================================
        Schema::table('cabor', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_peserta_id')->nullable()->after('deskripsi');
            $table->foreign('kategori_peserta_id')->references('id')->on('mst_kategori_peserta')->onDelete('set null');
        });

        // Migrate data dari cabor_kategori ke cabor (ambil kategori_peserta_id dari kategori pertama)
        // Fix: gunakan MIN() untuk comply dengan sql_mode=only_full_group_by
        DB::statement('
            UPDATE cabor c
            INNER JOIN (
                SELECT cabor_id, MIN(kategori_peserta_id) as kategori_peserta_id
                FROM cabor_kategori
                WHERE kategori_peserta_id IS NOT NULL
                GROUP BY cabor_id
            ) ck ON c.id = ck.cabor_id
            SET c.kategori_peserta_id = ck.kategori_peserta_id
        ');

        // =====================================================
        // PART 2: Buat cabor_kategori_id nullable di pivot tables
        // =====================================================

        // 2a. cabor_kategori_atlet - ATLET HANYA BISA SATU CABOR
        // Unique: cabor_id + atlet_id
        $this->dropForeignKeyIfExists('cabor_kategori_atlet', 'cabor_kategori_atlet_cabor_kategori_id_foreign');
        $this->dropIndexIfExists('cabor_kategori_atlet', 'cabor_kategori_atlet_unique');

        DB::statement('ALTER TABLE cabor_kategori_atlet MODIFY cabor_kategori_id BIGINT UNSIGNED NULL');

        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            // Unique: cabor_id + atlet_id (atlet hanya bisa di satu cabor)
            $table->unique(['cabor_id', 'atlet_id'], 'cabor_atlet_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('set null');
        });

        // 2b. cabor_kategori_pelatih - PELATIH BISA DI BEBERAPA CABOR
        // Unique: cabor_id + cabor_kategori_id + pelatih_id (atau cabor_id + pelatih_id jika kategori null)
        $this->dropForeignKeyIfExists('cabor_kategori_pelatih', 'cabor_kategori_pelatih_cabor_kategori_id_foreign');
        $this->dropIndexIfExists('cabor_kategori_pelatih', 'cabor_kategori_pelatih_unique');

        DB::statement('ALTER TABLE cabor_kategori_pelatih MODIFY cabor_kategori_id BIGINT UNSIGNED NULL');

        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            // Unique: cabor_id + cabor_kategori_id + pelatih_id
            // Ini mengizinkan pelatih di beberapa cabor, dan beberapa kategori dalam satu cabor
            $table->unique(['cabor_id', 'cabor_kategori_id', 'pelatih_id'], 'cabor_kategori_pelatih_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('set null');
        });

        // 2c. cabor_kategori_tenaga_pendukung - TENAGA PENDUKUNG BISA DI BEBERAPA CABOR
        // Unique: cabor_id + cabor_kategori_id + tenaga_pendukung_id
        $this->dropForeignKeyIfExists('cabor_kategori_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung_cabor_kategori_id_foreign');
        $this->dropIndexIfExists('cabor_kategori_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung_unique');

        DB::statement('ALTER TABLE cabor_kategori_tenaga_pendukung MODIFY cabor_kategori_id BIGINT UNSIGNED NULL');

        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            // Unique: cabor_id + cabor_kategori_id + tenaga_pendukung_id
            // Ini mengizinkan tenaga pendukung di beberapa cabor, dan beberapa kategori dalam satu cabor
            $table->unique(['cabor_id', 'cabor_kategori_id', 'tenaga_pendukung_id'], 'cabor_kategori_tenaga_pendukung_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Rollback Part 2: Kembalikan unique constraint lama

        // cabor_kategori_atlet
        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->dropForeign(['cabor_kategori_id']);
        });
        $this->dropIndexIfExists('cabor_kategori_atlet', 'cabor_atlet_unique');

        DB::statement('ALTER TABLE cabor_kategori_atlet MODIFY cabor_kategori_id BIGINT UNSIGNED NOT NULL');

        Schema::table('cabor_kategori_atlet', function (Blueprint $table) {
            $table->unique(['cabor_kategori_id', 'atlet_id'], 'cabor_kategori_atlet_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
        });

        // cabor_kategori_pelatih
        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->dropForeign(['cabor_kategori_id']);
        });
        $this->dropIndexIfExists('cabor_kategori_pelatih', 'cabor_kategori_pelatih_unique');

        DB::statement('ALTER TABLE cabor_kategori_pelatih MODIFY cabor_kategori_id BIGINT UNSIGNED NOT NULL');

        Schema::table('cabor_kategori_pelatih', function (Blueprint $table) {
            $table->unique(['cabor_kategori_id', 'pelatih_id'], 'cabor_kategori_pelatih_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
        });

        // cabor_kategori_tenaga_pendukung
        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->dropForeign(['cabor_kategori_id']);
        });
        $this->dropIndexIfExists('cabor_kategori_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung_unique');

        DB::statement('ALTER TABLE cabor_kategori_tenaga_pendukung MODIFY cabor_kategori_id BIGINT UNSIGNED NOT NULL');

        Schema::table('cabor_kategori_tenaga_pendukung', function (Blueprint $table) {
            $table->unique(['cabor_kategori_id', 'tenaga_pendukung_id'], 'cabor_kategori_tenaga_pendukung_unique');
            $table->foreign('cabor_kategori_id')->references('id')->on('cabor_kategori')->onDelete('cascade');
        });

        // Rollback Part 1: Hapus kategori_peserta_id dari cabor
        Schema::table('cabor', function (Blueprint $table) {
            $table->dropForeign(['kategori_peserta_id']);
            $table->dropColumn('kategori_peserta_id');
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

    /**
     * Helper: Drop index if exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        
        if (count($indexes) > 0) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }
};
