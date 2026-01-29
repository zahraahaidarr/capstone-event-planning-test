<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_event_civil_cases', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('submission_id')
                ->constrained('post_event_submissions')
                ->cascadeOnDelete();

            $table->enum('case_type', ['injury','fainting','panic','other'])
                ->default('injury');

            $table->unsignedTinyInteger('age')->nullable();

            $table->enum('gender', ['male','female','other'])
                ->nullable();

            $table->enum('action_taken', ['bandage','on-site-care','hospital-referral','other'])
                ->default('on-site-care');

            $table->string('notes', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_event_civil_cases');
    }
};
