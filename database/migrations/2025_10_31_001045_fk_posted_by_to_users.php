<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1) Make posted_by nullable big integer (works on both)
        Schema::table('announcements', function (Blueprint $t) {
            $t->unsignedBigInteger('posted_by')->nullable()->change();
        });

        if ($driver === 'mysql') {
            // --- MySQL: drop FK by name using INFORMATION_SCHEMA + DATABASE()
            if ($fk = DB::selectOne("
                SELECT CONSTRAINT_NAME name
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'announcements'
                  AND COLUMN_NAME  = 'posted_by'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ")) {
                DB::statement("ALTER TABLE `announcements` DROP FOREIGN KEY `{$fk->name}`");
            }

            // Map employees.employee_id -> employees.user_id
            DB::statement("
                UPDATE announcements a
                JOIN employees e ON e.employee_id = a.posted_by
                SET a.posted_by = e.user_id
                WHERE a.posted_by IS NOT NULL
            ");

            // Add FK to users(id)
            DB::statement("
                ALTER TABLE `announcements`
                ADD CONSTRAINT `announcements_posted_by_foreign`
                FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`)
                ON DELETE SET NULL
            ");
        }

        if ($driver === 'pgsql') {
            // --- PostgreSQL: drop constraints safely (names vary)
            DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_posted_by_foreign');
            DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_posted_by_fkey');

            // Map employees.employee_id -> employees.user_id (Postgres syntax)
            DB::statement("
                UPDATE announcements a
                SET posted_by = e.user_id
                FROM employees e
                WHERE a.posted_by = e.employee_id
                  AND a.posted_by IS NOT NULL
            ");

            // Add FK to users(id)
            DB::statement("
                ALTER TABLE announcements
                ADD CONSTRAINT announcements_posted_by_foreign
                FOREIGN KEY (posted_by) REFERENCES users(id)
                ON DELETE SET NULL
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Drop FK to users
            if ($fk = DB::selectOne("
                SELECT CONSTRAINT_NAME name
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'announcements'
                  AND COLUMN_NAME  = 'posted_by'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ")) {
                DB::statement("ALTER TABLE `announcements` DROP FOREIGN KEY `{$fk->name}`");
            }

            // Optional: revert type
            Schema::table('announcements', function (Blueprint $t) {
                $t->unsignedBigInteger('posted_by')->nullable()->change();
            });

            // Recreate FK to employees(employee_id)
            DB::statement("
                ALTER TABLE `announcements`
                ADD CONSTRAINT `announcements_posted_by_foreign`
                FOREIGN KEY (`posted_by`) REFERENCES `employees`(`employee_id`)
                ON DELETE SET NULL
            ");
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_posted_by_foreign');
            DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_posted_by_fkey');

            // Optional: revert type (keep bigint to avoid issues)
            Schema::table('announcements', function (Blueprint $t) {
                $t->unsignedBigInteger('posted_by')->nullable()->change();
            });

            // Recreate FK to employees(employee_id)
            DB::statement("
                ALTER TABLE announcements
                ADD CONSTRAINT announcements_posted_by_foreign
                FOREIGN KEY (posted_by) REFERENCES employees(employee_id)
                ON DELETE SET NULL
            ");
        }
    }
};
