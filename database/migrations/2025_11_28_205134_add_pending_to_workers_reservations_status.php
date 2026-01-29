<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL supports ENUM change
            Schema::table('workers_reservations', function (Blueprint $table) {
                $table->enum('status', [
                    'PENDING',
                    'RESERVED',
                    'CHECKED_IN',
                    'CHECKED_OUT',
                    'COMPLETED',
                    'NO_SHOW',
                    'CANCELLED',
                ])->default('PENDING')->change();
            });
        }

        if ($driver === 'pgsql') {
            // PostgreSQL: VARCHAR + CHECK constraint

            // Drop old constraint if exists
            DB::statement("
                ALTER TABLE workers_reservations
                DROP CONSTRAINT IF EXISTS workers_reservations_status_check
            ");

            // Ensure type
            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status TYPE VARCHAR(255)
            ");

            // Default + not null
            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status SET DEFAULT 'PENDING'
            ");
            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status SET NOT NULL
            ");

            // Add CHECK constraint separately (Postgres-safe)
            DB::statement("
                ALTER TABLE workers_reservations
                ADD CONSTRAINT workers_reservations_status_check
                CHECK (status IN (
                    'PENDING',
                    'RESERVED',
                    'CHECKED_IN',
                    'CHECKED_OUT',
                    'COMPLETED',
                    'NO_SHOW',
                    'CANCELLED'
                ))
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            Schema::table('workers_reservations', function (Blueprint $table) {
                $table->enum('status', [
                    'RESERVED',
                    'CHECKED_IN',
                    'CHECKED_OUT',
                    'COMPLETED',
                    'NO_SHOW',
                    'CANCELLED',
                ])->default('RESERVED')->change();
            });
        }

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE workers_reservations
                DROP CONSTRAINT IF EXISTS workers_reservations_status_check
            ");

            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status SET DEFAULT 'RESERVED'
            ");
        }
    }
};
