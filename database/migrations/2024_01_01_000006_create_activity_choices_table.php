<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FE-04 / FE-05 / TE-05
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            // Eén reiziger kan een specifieke activiteit maar één keer kiezen.
            $table->unique(['user_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_choices');
    }
};
