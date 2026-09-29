<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('acc_reviews')
            ->whereNull('certificate_hours')
            ->update([
                'certificate_hours' => DB::raw('COALESCE(original_hours, approved_hours)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing review data is intentionally preserved when rolling back this backfill.
    }
};
