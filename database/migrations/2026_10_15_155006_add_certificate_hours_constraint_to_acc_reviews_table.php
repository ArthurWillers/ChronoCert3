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
        DB::statement('ALTER TABLE acc_reviews ADD CONSTRAINT acc_reviews_certificate_hours_check CHECK (certificate_hours IS NULL OR certificate_hours > 0) NOT VALID');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE acc_reviews DROP CONSTRAINT acc_reviews_certificate_hours_check');
    }
};
