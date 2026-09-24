<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vaste checklistpunten die de coördinator per reis vastlegt, en welke reiziger ze heeft
// afgevinkt. Los van checklist_items: dat zijn de eigen punten van een reiziger.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('trip_checklist_item_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_checklist_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['trip_checklist_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_checklist_item_user');
        Schema::dropIfExists('trip_checklist_items');
    }
};
