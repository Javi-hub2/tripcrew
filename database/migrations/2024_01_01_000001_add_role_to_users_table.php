<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This migration extends Laravel's default `users` table (from the
// framework's stock 0001_01_01_000000_create_users_table.php).
// FE-01/FE-02: reizigers moeten eerst hun account activeren (wachtwoord
// instellen) voor ze kunnen inloggen; TE-03: role bepaalt autorisatie.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['reiziger', 'coordinator'])->default('reiziger')->after('email');
            // Null zolang de reiziger zijn account nog niet heeft geactiveerd (FE-01).
            $table->timestamp('activated_at')->nullable()->after('password');
            $table->string('activation_token', 64)->nullable()->unique()->after('activated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'activated_at', 'activation_token']);
        });
    }
};
