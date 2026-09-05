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
        Schema::create('affiliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->string('email');
            $table->string('registration_number', 64)->nullable();
            $table->timestamp('deactivated_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'deactivated_at']);
            $table->index(['course_id', 'type', 'deactivated_at']);
            $table->index(['course_id', 'registration_number']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE affiliations
            ADD CONSTRAINT affiliations_type_scope_check CHECK (
                (type = 'administrator' AND course_id IS NULL AND registration_number IS NULL)
                OR (type = 'coordinator' AND course_id IS NOT NULL AND registration_number IS NULL)
                OR (type = 'student' AND course_id IS NOT NULL AND registration_number IS NOT NULL)
            )
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX affiliations_active_global_administrator_unique
            ON affiliations (user_id, type)
            WHERE type = 'administrator' AND course_id IS NULL AND deactivated_at IS NULL
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX affiliations_active_course_type_unique
            ON affiliations (user_id, course_id, type)
            WHERE course_id IS NOT NULL AND deactivated_at IS NULL
        SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX affiliations_active_student_registration_unique
            ON affiliations (course_id, registration_number)
            WHERE type = 'student' AND registration_number IS NOT NULL AND deactivated_at IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliations');
    }
};
