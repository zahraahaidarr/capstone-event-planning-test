<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->bigIncrements('announcement_id');

            $table->string('title');
            $table->text('body');

            // FK to employees.employee_id (nullable, set null on delete)
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->foreign('posted_by')
                  ->references('employee_id')
                  ->on('employees')
                  ->nullOnDelete();

            $table->string('audience', 50)->default('ALL'); // ALL / WORKERS / EMPLOYEES / ADMINS / CUSTOM

            // Use Laravel timestamps (created_at + updated_at)
            $table->timestamps();

            // Optional expiry
            $table->timestamp('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
