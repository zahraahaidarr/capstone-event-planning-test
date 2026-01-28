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
            // MySQL: real ENUM
            Schema::table('announcements', function (Blueprint $table) {
                $table->enum('audience', ['workers','employees','both'])
                      ->default('workers')
                      ->nullable(false)
                      ->change();
            });
        }

        if ($driver === 'pgsql') {
            // PostgreSQL: use VARCHAR + CHECK constraint

            // Ensure column is varchar
            DB::statement("
                ALTER TABLE announcements
                ALTER COLUMN audience TYPE VARCHAR(50)
            ");

            // Set default & NOT NULL
            DB::statement("
                ALTER TABLE announcements
                ALTER COLUMN audience SET DEFAULT 'workers'
            ");
            DB::statement("
                ALTER TABLE announcements
                ALTER COLUMN audience SET NOT NULL
            ");

            // Add CHECK constraint separately (Postgres correct way)
            DB::statement("
                ALTER TABLE announcements
                ADD CONSTRAINT announcements_audience_check
                CHECK (audience IN ('workers','employees','both'))
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('audience', 50)
                      ->default('workers')
                      ->nullable(false)
                      ->change();
            });
        }

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE announcements
                DROP CONSTRAINT IF EXISTS announcements_audience_check
            ");
        }
    }
};
