<?php

use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the coordinator dashboard shows only actionable statistics from the active course', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $otherStudent = Affiliation::factory()->student()->for($otherCourse)->create();

    AccCategory::factory()->for($course)->create();
    AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
        'status' => SubmissionStatus::Submitted,
    ]);
    AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
        'status' => SubmissionStatus::UnderReview,
    ]);
    AccSubmission::factory()->create([
        'student_affiliation_id' => $otherStudent->getKey(),
        'submitted_by_affiliation_id' => $otherStudent->getKey(),
        'status' => SubmissionStatus::Submitted,
    ]);

    $this->actingAs($coordinator->user)
        ->withSession(['active_affiliation_id' => $coordinator->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Aguardando início')
        ->assertSeeText('Em análise')
        ->assertSeeText('Discentes ativos')
        ->assertSeeText('Próximas análises')
        ->assertSeeText('1');
});

test('the student dashboard summarizes accepted hours using category limits', function () {
    $course = Course::factory()->create(['required_acc_hours' => 60, 'minimum_area_percentage' => 50]);
    $student = Affiliation::factory()->student()->for($course)->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $category = AccCategory::factory()->for($course)->create(['max_hours' => 10]);
    $acceptedSubmission = AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
        'status' => SubmissionStatus::Approved,
    ]);
    AccReview::factory()->create([
        'acc_submission_id' => $acceptedSubmission->getKey(),
        'reviewer_affiliation_id' => $coordinator->getKey(),
        'acc_category_id' => $category->getKey(),
        'certificate_hours' => 12,
        'is_area_related' => true,
        'normalized_title' => 'Curso de extensão',
        'completed_at' => now(),
    ]);
    AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
        'status' => SubmissionStatus::UnderReview,
    ]);

    $this->actingAs($student->user)
        ->withSession(['active_affiliation_id' => $student->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Aguardando análise')
        ->assertSeeText('Documentos aceitos')
        ->assertSeeText('Horas nas categorias')
        ->assertSeeText('10,00 h')
        ->assertSeeText('Documentos recentes');
});

test('the administrator dashboard excludes categories and administrative shortcuts', function () {
    $administrator = Affiliation::factory()->administrator()->create();

    $this->actingAs($administrator->user)
        ->withSession(['active_affiliation_id' => $administrator->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Categorias ativas')
        ->assertDontSeeText('Atalhos administrativos')
        ->assertDontSeeText('Gerenciar categorias');
});
