<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Briefing: reiziger bekijkt "dagprogramma en praktische informatie". Eén vrij tekstveld,
// omdat elke reis andere informatie heeft (verzamelplaats, verblijf, noodnummer, …).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->text('practical_info')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('practical_info');
        });
    }
};
