<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('post_event_submissions', function (Blueprint $table) {
        $table->unsignedTinyInteger('worker_rating')
              ->nullable()
              ->after('owner_rating');   // place it after owner_rating
    });
}

public function down()
{
    Schema::table('post_event_submissions', function (Blueprint $table) {
        $table->dropColumn('worker_rating');
    });
}

};
