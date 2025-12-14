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
        Schema::create('kirjad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmad')->onDelete('cascade');
            $table->string('gmail_message_id')->unique();
            $table->string('gmail_thread_id')->index();
            $table->string('saatja_email');
            $table->string('saatja_nimi')->nullable();
            $table->string('saaja_email');
            $table->string('teema');
            $table->longText('sisu_text')->nullable();
            $table->longText('sisu_html')->nullable();
            $table->enum('suund', ['sisse', 'valja'])->default('sisse');
            $table->enum('staatus', ['uus', 'loetud', 'tootluses', 'valmis', 'ignoreeritud'])->default('uus');
            $table->boolean('on_manuseid')->default(false);
            $table->timestamp('gmail_kuupaev');
            $table->timestamps();
            
            $table->index(['firma_id', 'staatus']);
            $table->index(['firma_id', 'gmail_kuupaev']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kirjad');
    }
};
