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
                ALTER TABLE users
                MODIFY status ENUM('ACTIVE', 'SUSPENDED', 'BANNED', 'PENDING')
                NOT NULL DEFAULT 'PENDING'
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: don't use ENUM here (to keep it simple & compatible)
            DB::statement("ALTER TABLE users ALTER COLUMN status TYPE VARCHAR(20)");
            DB::statement("UPDATE users SET status = 'PENDING' WHERE status IS NULL");
            DB::statement("ALTER TABLE users ALTER COLUMN status SET DEFAULT 'PENDING'");
            DB::statement("ALTER TABLE users ALTER COLUMN status SET NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY status ENUM('ACTIVE', 'SUSPENDED', 'PENDING')
                NOT NULL DEFAULT 'PENDING'
            ");
        } elseif ($driver === 'pgsql') {
            // Keep it as VARCHAR on rollback too (safe)
            DB::statement("ALTER TABLE users ALTER COLUMN status TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE users ALTER COLUMN status SET DEFAULT 'PENDING'");
        }
    }
};
