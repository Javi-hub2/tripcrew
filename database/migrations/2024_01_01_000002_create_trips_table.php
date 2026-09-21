<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FE-06 / TE-04
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        // Koppeltabel: welke reizigers horen bij welke reis.
        // Nodig omdat users geen directe FK naar trips heeft in de ERD,
        // maar FE-02 ("reiziger komt bij zijn eigen reis") vereist deze relatie.
        Schema::create('trip_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['trip_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_user');
        Schema::dropIfExists('trips');
    }
};
