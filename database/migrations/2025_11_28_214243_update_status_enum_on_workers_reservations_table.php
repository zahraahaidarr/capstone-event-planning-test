<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE workers_reservations
                MODIFY status ENUM(
                    'PENDING',
                    'RESERVED',
                    'CHECKED_IN',
                    'CHECKED_OUT',
                    'COMPLETED',
                    'NO_SHOW',
                    'CANCELLED',
                    'REJECTED'
                ) NOT NULL DEFAULT 'PENDING'
            ");
        } elseif ($driver === 'pgsql') {
            // Update CHECK constraint to include REJECTED
            DB::statement("
                ALTER TABLE workers_reservations
                DROP CONSTRAINT IF EXISTS workers_reservations_status_check
            ");

            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status TYPE VARCHAR(255)
            ");

            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status SET DEFAULT 'PENDING'
            ");
            DB::statement("
                ALTER TABLE workers_reservations
                ALTER COLUMN status SET NOT NULL
            ");

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
                    'CANCELLED',
                    'REJECTED'
                ))
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE workers_reservations
                MODIFY status ENUM(
                    'PENDING',
                    'RESERVED',
                    'CHECKED_IN',
                    'CHECKED_OUT',
                    'COMPLETED',
                    'NO_SHOW',
                    'CANCELLED'
                ) NOT NULL DEFAULT 'PENDING'
            ");
        } elseif ($driver === 'pgsql') {
            // Remove REJECTED from CHECK constraint
            DB::statement("
                ALTER TABLE workers_reservations
                DROP CONSTRAINT IF EXISTS workers_reservations_status_check
            ");

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
};
