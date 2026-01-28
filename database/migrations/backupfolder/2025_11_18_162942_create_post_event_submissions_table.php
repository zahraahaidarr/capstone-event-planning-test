<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_event_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('worker_reservation_id');
$table->foreign('worker_reservation_id')
      ->references('reservation_id')
      ->on('workers_reservations')
      ->cascadeOnDelete();


            // Denormalized for easier queries
            $table->unsignedBigInteger('event_id');
$table->foreign('event_id')
      ->references('event_id')
      ->on('events')
      ->cascadeOnDelete();


            // Workers table uses worker_id as PK
            $table->unsignedBigInteger('worker_id');
            $table->foreign('worker_id')
                ->references('worker_id')
                ->on('workers')
                ->cascadeOnDelete();

            $table->foreignId('work_role_id')
      ->constrained('work_roles', 'role_id')
      ->cascadeOnDelete();

            // 'organizer', 'civil', 'media', 'tech', 'cleaner', 'decorator', 'cooking', 'waiter'
            $table->string('role_slug', 50);

            $table->text('general_notes')->nullable();
            $table->json('data')->nullable(); // role-specific JSON

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable(); // admin/employee user
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->text('review_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_event_submissions');
    }
};
