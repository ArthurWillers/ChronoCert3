<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->unsignedBigInteger('context_course_id')->nullable();
            $table->index(['context_course_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex(['context_course_id', 'created_at']);
            $table->dropColumn('context_course_id');
        });
    }
};
