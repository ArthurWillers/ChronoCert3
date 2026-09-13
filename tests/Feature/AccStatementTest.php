<?php

use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the student statement counts only approved submissions and preserves decision snapshots', function () {
    $course = Course::factory()->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $category = AccCategory::factory()->for($course)->create(['name' => 'Eventos', 'max_hours' => 20]);
    $approvedSubmission = statementSubmission($student, SubmissionStatus::Approved);
    AccReview::factory()->create([
        'acc_submission_id' => $approvedSubmission->getKey(),
        'reviewer_affiliation_id' => $coordinator->getKey(),
        'acc_category_id' => $category->getKey(),
        'original_title' => 'Evento',
        'normalized_title' => 'Evento acadêmico',
        'original_hours' => 8,
        'approved_hours' => 8,
        'category_snapshot' => $category->academicSnapshot(),
        'rules_snapshot' => ['approved_hours' => '8.00'],
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);
    $rejectedSubmission = statementSubmission($student, SubmissionStatus::Rejected);
    AccReview::factory()->create([
        'acc_submission_id' => $rejectedSubmission->getKey(),
        'reviewer_affiliation_id' => $coordinator->getKey(),
        'original_title' => 'Documento inválido',
        'rejection_reason' => 'Documento ilegível.',
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);
    $category->update(['name' => 'Eventos atualizados']);

    reviewActingAs($this, $student)
        ->get(route('statements.index'))
        ->assertOk()
        ->assertSeeText('8,00 h')
        ->assertSeeText('Evento acadêmico')
        ->assertSeeText('Eventos')
        ->assertSeeText('Documento ilegível.')
        ->assertDontSeeText('16,00 h');
});

test('the coordinator statement is isolated to students in the active course', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $otherStudent = Affiliation::factory()->student()->for($otherCourse)->create();
    $category = AccCategory::factory()->for($course)->create(['max_hours' => 20]);
    $submission = statementSubmission($student, SubmissionStatus::Approved);
    AccReview::factory()->create([
        'acc_submission_id' => $submission->getKey(),
        'reviewer_affiliation_id' => $coordinator->getKey(),
        'acc_category_id' => $category->getKey(),
        'original_title' => 'Atividade',
        'normalized_title' => 'Atividade',
        'approved_hours' => 5,
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    reviewActingAs($this, $coordinator)
        ->get(route('statements.index'))
        ->assertOk()
        ->assertSeeText($student->user->name)
        ->assertSeeText('5,00 h')
        ->assertDontSeeText($otherStudent->user->name);
});

function statementSubmission(Affiliation $student, SubmissionStatus $status): AccSubmission
{
    return AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
        'status' => $status,
        'review_started_at' => now()->subDay(),
        'reviewed_at' => now(),
        'rejected_at' => $status === SubmissionStatus::Rejected ? now() : null,
        'purge_at' => $status === SubmissionStatus::Rejected ? now()->addDays(30) : null,
    ]);
}
