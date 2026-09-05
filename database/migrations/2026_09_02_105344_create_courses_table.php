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
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('required_acc_hours', 7, 2)->nullable();
            $table->decimal('minimum_area_percentage', 5, 2)->nullable();
            $table->timestamp('deactivated_at')->nullable()->index();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE courses
            ADD CONSTRAINT courses_acc_requirements_check CHECK (
                (required_acc_hours IS NULL OR required_acc_hours > 0)
                AND (
                    minimum_area_percentage IS NULL
                    OR (
                        required_acc_hours IS NOT NULL
                        AND minimum_area_percentage > 0
                        AND minimum_area_percentage <= 100
                    )
                )
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
