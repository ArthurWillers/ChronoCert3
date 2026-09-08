<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE acc_categories DROP CONSTRAINT acc_categories_rules_check');

        Schema::table('acc_categories', function (Blueprint $table): void {
            $table->dropColumn(['accepts_multiple', 'document_required', 'allowed_mime_types', 'max_file_size_bytes']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE acc_categories
            ADD CONSTRAINT acc_categories_rules_check CHECK (
                max_hours > 0 AND length(trim(name)) > 0
                AND ((deactivated_at IS NULL AND deactivation_reason IS NULL)
                    OR (deactivated_at IS NOT NULL AND deactivation_reason IS NOT NULL
                        AND length(trim(deactivation_reason)) > 0))
            )
        SQL);
    }

    /**
     * Restore the former columns using the fixed rules; previous settings remain in audit snapshots.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE acc_categories DROP CONSTRAINT acc_categories_rules_check');

        Schema::table('acc_categories', function (Blueprint $table): void {
            $table->boolean('accepts_multiple')->default(true);
            $table->boolean('document_required')->default(false);
            $table->jsonb('allowed_mime_types')->default('["application/pdf","image/jpeg","image/png","image/webp","image/bmp"]');
            $table->bigInteger('max_file_size_bytes')->default(10485760);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE acc_categories
            ADD CONSTRAINT acc_categories_rules_check CHECK (
                max_hours > 0 AND max_file_size_bytes > 0
                AND length(trim(name)) > 0
                AND CASE WHEN jsonb_typeof(allowed_mime_types) = 'array'
                    THEN jsonb_array_length(allowed_mime_types) > 0 ELSE false END
                AND ((deactivated_at IS NULL AND deactivation_reason IS NULL)
                    OR (deactivated_at IS NOT NULL AND deactivation_reason IS NOT NULL
                        AND length(trim(deactivation_reason)) > 0))
            )
        SQL);
    }
};
