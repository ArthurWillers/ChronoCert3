<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_hours', 7, 2);
            $table->boolean('accepts_multiple');
            $table->boolean('document_required');
            $table->jsonb('allowed_mime_types');
            $table->bigInteger('max_file_size_bytes');
            $table->text('guidance')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();
            $table->foreignId('created_by_affiliation_id')->constrained('affiliations')->restrictOnDelete();
            $table->timestamps();
            $table->index(['course_id', 'deactivated_at']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX acc_categories_active_course_name_unique
            ON acc_categories (course_id, name) WHERE deactivated_at IS NULL
        SQL);
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

    public function down(): void
    {
        Schema::dropIfExists('acc_categories');
    }
};
