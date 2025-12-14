<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tegevused', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmad')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('kiri_id')->nullable()->constrained('kirjad')->onDelete('cascade');
            $table->foreignId('manus_id')->nullable()->constrained('manused')->onDelete('cascade');
            $table->enum('tyyp', [
                'kiri_saadud',
                'kiri_loetud', 
                'kiri_vastatud',
                'arve_tuvastatud',
                'arve_sisestatud',
                'markus_lisatud',
                'staatus_muudetud'
            ]);
            $table->text('kirjeldus')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['firma_id', 'created_at']);
            $table->index(['kiri_id', 'tyyp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tegevused');
    }
};
