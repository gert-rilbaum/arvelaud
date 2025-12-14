<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE tegevused MODIFY COLUMN tyyp ENUM(
            'kiri_saadud',
            'kiri_loetud',
            'kiri_vastatud',
            'arve_tuvastatud',
            'arve_sisestatud',
            'markus_lisatud',
            'staatus_muudetud',
            'manus_arve',
            'manus_muu'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tegevused MODIFY COLUMN tyyp ENUM(
            'kiri_saadud',
            'kiri_loetud',
            'kiri_vastatud',
            'arve_tuvastatud',
            'arve_sisestatud',
            'markus_lisatud',
            'staatus_muudetud'
        )");
    }
};
