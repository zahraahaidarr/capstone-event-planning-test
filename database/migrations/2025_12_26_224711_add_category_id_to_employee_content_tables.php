<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();

        });

        Schema::table('employee_reels', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();

        });

        Schema::table('employee_stories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('employee_posts', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('employee_reels', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('employee_stories', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};
