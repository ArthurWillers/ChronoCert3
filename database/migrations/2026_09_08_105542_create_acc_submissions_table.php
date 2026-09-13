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
        Schema::create('acc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_affiliation_id')->constrained('affiliations')->restrictOnDelete();
            $table->foreignId('submitted_by_affiliation_id')->constrained('affiliations')->restrictOnDelete();
            $table->string('origin', 32);
            $table->string('status', 32);
            $table->timestamp('submitted_at');
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('purge_at')->nullable();
            $table->timestamps();

            $table->index(['student_affiliation_id', 'status', 'submitted_at']);
            $table->index('submitted_by_affiliation_id');
            $table->index(['status', 'submitted_at']);
            $table->index('purge_at');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE acc_submissions
            ADD CONSTRAINT acc_submissions_origin_check CHECK (
                origin IN ('student', 'coordinator')
            )
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE acc_submissions
            ADD CONSTRAINT acc_submissions_status_check CHECK (
                status IN ('submitted', 'under_review', 'rejected', 'approved')
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_submissions');
    }
};
