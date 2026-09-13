<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acc_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acc_submission_id')->unique()->constrained('acc_submissions')->restrictOnDelete();
            $table->foreignId('reviewer_affiliation_id')->constrained('affiliations')->restrictOnDelete();
            $table->foreignId('acc_category_id')->nullable()->constrained('acc_categories')->restrictOnDelete();
            $table->string('original_title');
            $table->string('normalized_title')->nullable();
            $table->decimal('original_hours', 8, 2)->nullable();
            $table->decimal('approved_hours', 8, 2)->nullable();
            $table->text('classification_justification')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->jsonb('category_snapshot')->nullable();
            $table->jsonb('rules_snapshot')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['reviewer_affiliation_id', 'started_at']);
            $table->index(['acc_category_id', 'completed_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE acc_reviews
            ADD CONSTRAINT acc_reviews_hours_check CHECK (
                (original_hours IS NULL OR original_hours > 0)
                AND (approved_hours IS NULL OR approved_hours > 0)
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_reviews');
    }
};
