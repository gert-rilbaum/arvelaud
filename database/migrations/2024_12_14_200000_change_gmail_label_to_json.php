<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Esmalt konverdi olemasolevad andmed JSON formaati
        $firmad = DB::table('firmad')->whereNotNull('gmail_label')->get();
        foreach ($firmad as $firma) {
            DB::table('firmad')
                ->where('id', $firma->id)
                ->update(['gmail_label' => json_encode([$firma->gmail_label])]);
        }

        // Muuda veeru nimi ja tüüp
        Schema::table('firmad', function (Blueprint $table) {
            $table->renameColumn('gmail_label', 'gmail_labels');
        });

        Schema::table('firmad', function (Blueprint $table) {
            $table->json('gmail_labels')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('firmad', function (Blueprint $table) {
            $table->renameColumn('gmail_labels', 'gmail_label');
        });

        Schema::table('firmad', function (Blueprint $table) {
            $table->string('gmail_label')->nullable()->change();
        });

        // Konverdi tagasi stringiks (võta esimene)
        $firmad = DB::table('firmad')->whereNotNull('gmail_label')->get();
        foreach ($firmad as $firma) {
            $labels = json_decode($firma->gmail_label, true);
            DB::table('firmad')
                ->where('id', $firma->id)
                ->update(['gmail_label' => $labels[0] ?? null]);
        }
    }
};
