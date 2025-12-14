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
        Schema::create('firmad', function (Blueprint $table) {
            $table->id();
            $table->string('nimi');
            $table->string('registrikood')->nullable();
            $table->string('gmail_label')->unique();
            $table->string('email')->nullable();
            $table->string('telefon')->nullable();
            $table->text('aadress')->nullable();
            $table->string('merit_api_id')->nullable();
            $table->string('merit_api_key')->nullable();
            $table->boolean('aktiivne')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmad');
    }
};
