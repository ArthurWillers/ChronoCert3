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
        Schema::table('acc_reviews', function (Blueprint $table): void {
            $table->decimal('certificate_hours', 8, 2)->nullable();
            $table->boolean('is_area_related')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acc_reviews', function (Blueprint $table): void {
            $table->dropColumn(['certificate_hours', 'is_area_related']);
        });
    }
};
