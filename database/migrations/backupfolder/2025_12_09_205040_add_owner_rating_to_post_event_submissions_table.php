<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_event_submissions', function (Blueprint $table) {
            // rating 1–5 for event owner
            $table->unsignedTinyInteger('owner_rating')
                  ->nullable()
                  ->after('status');   // or after('data') if you prefer
        });
    }

    public function down(): void
    {
        Schema::table('post_event_submissions', function (Blueprint $table) {
            $table->dropColumn('owner_rating');
        });
    }
};

