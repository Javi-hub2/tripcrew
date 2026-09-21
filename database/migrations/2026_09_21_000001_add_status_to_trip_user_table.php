<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_user', function (Blueprint $table) {
            // string, geen enum: SQLite (de testdatabase) kent geen enum-kolommen.
            $table->string('status')->default(RegistrationStatus::Pending->value)->after('user_id');
            $table->timestamp('requested_at')->nullable()->after('status');
            $table->timestamp('decided_at')->nullable()->after('requested_at');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
        });

        // Bestaande koppelingen (seeder) waren impliciet goedgekeurd.
        DB::table('trip_user')->update([
            'status' => RegistrationStatus::Approved->value,
            'requested_at' => now(),
            'decided_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('trip_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['status', 'requested_at', 'decided_at']);
        });
    }
};
