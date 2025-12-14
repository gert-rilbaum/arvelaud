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
        Schema::create('manused', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kiri_id')->constrained('kirjad')->onDelete('cascade');
            $table->foreignId('firma_id')->constrained('firmad')->onDelete('cascade');
            $table->string('gmail_attachment_id');
            $table->string('failinimi');
            $table->string('mime_type');
            $table->integer('suurus')->nullable(); // baitides
            $table->string('salvestatud_path')->nullable();
            $table->boolean('on_arve')->default(false);
            $table->json('ocr_andmed')->nullable();
            $table->enum('arve_staatus', ['tuvastamata', 'tuvastatud', 'sisestatud', 'ignoreeritud'])->default('tuvastamata');
            $table->timestamps();
            
            $table->index(['firma_id', 'on_arve']);
            $table->index(['firma_id', 'arve_staatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manused');
    }
};
