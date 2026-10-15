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
        Schema::table('acc_submissions', function (Blueprint $table) {
            $table->string('document_sha256', 64)->nullable();
        });

        DB::statement("CREATE UNIQUE INDEX acc_submissions_active_document_unique ON acc_submissions (student_affiliation_id, document_sha256) WHERE status IN ('submitted', 'under_review', 'approved')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX acc_submissions_active_document_unique');

        Schema::table('acc_submissions', function (Blueprint $table) {
            $table->dropColumn('document_sha256');
        });
    }
};
