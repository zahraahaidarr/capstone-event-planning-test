<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_event_submission_files', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('submission_id')
                ->constrained('post_event_submissions')
                ->cascadeOnDelete();

            // e.g. 'civil_forms','media_files','tech_recording',
            //      'decorator_photos','cooking_photos', ...
            $table->string('section', 50)->nullable();

            $table->string('path');           // storage path
            $table->string('original_name');  // original filename
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_event_submission_files');
    }
};
