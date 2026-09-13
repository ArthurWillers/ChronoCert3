<?php

use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\AuditActivity;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

test('a coordinator starts an analysis for a submission in the active course', function () {
    [$submission, $student, $coordinator] = reviewScenario();

    reviewActingAs($this, $coordinator)
        ->post(route('submissions.review.store', $submission))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasNoErrors();

    expect($submission->refresh())
        ->status->toBe(SubmissionStatus::UnderReview)
        ->review_started_at->not->toBeNull();
    expect($submission->review)
        ->toBeInstanceOf(AccReview::class)
        ->reviewer_affiliation_id->toBe($coordinator->getKey());
    expect(AuditActivity::query()->where('event', 'submission.review_started')->exists())->toBeTrue();
});

test('a coordinator cannot analyze a submission from another course', function () {
    [$submission] = reviewScenario();
    $otherCoordinator = Affiliation::factory()->coordinator()->create();

    reviewActingAs($this, $otherCoordinator)
        ->post(route('submissions.review.store', $submission))
        ->assertForbidden();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Submitted);
});

test('a student cannot start an analysis', function () {
    [$submission, $student] = reviewScenario();

    reviewActingAs($this, $student)
        ->post(route('submissions.review.store', $submission))
        ->assertForbidden();
});

test('a coordinator classifies a submission with corrected academic data', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->patch(route('reviews.update', $review), validClassification($category))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasNoErrors();

    expect($review->refresh())
        ->normalized_title->toBe('Congresso de Tecnologia')
        ->approved_hours->toBe('8.00')
        ->acc_category_id->toBe($category->getKey())
        ->classification_justification->toBe('Título padronizado para o extrato.');
    expect(AuditActivity::query()->where('event', 'submission.reclassified')->exists())->toBeTrue();
});

test('a coordinator approves a classified submission and preserves the category snapshot', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startAndClassify($this, $submission, $coordinator, $category);

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.approve', $review))
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasNoErrors();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
    expect($review->refresh())
        ->completed_at->not->toBeNull()
        ->category_snapshot->toBeArray()
        ->and(data_get($review->category_snapshot, 'name'))->toBe($category->name)
        ->and(data_get($review->rules_snapshot, 'approved_hours'))->toBe('8.00');
});

test('a rejection requires a reason and schedules purge for thirty days', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => ''])
        ->assertSessionHasErrors('rejection_reason');

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => 'Documento sem identificação da atividade.'])
        ->assertSessionHasNoErrors();

    expect($submission->refresh())
        ->status->toBe(SubmissionStatus::Rejected)
        ->purge_at->toEqual($submission->rejected_at->copy()->addDays(30));
    expect($review->refresh()->rejection_reason)->toBe('Documento sem identificação da atividade.');
});

test('a completed review cannot receive another decision', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startAndClassify($this, $submission, $coordinator, $category);

    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $review))->assertRedirect();
    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => 'Decisão posterior indevida.'])
        ->assertForbidden();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
});

test('an inactive category cannot be approved', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startAndClassify($this, $submission, $coordinator, $category);
    $category->forceFill(['deactivated_at' => now(), 'deactivation_reason' => 'Regra encerrada.'])->save();

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.approve', $review))
        ->assertSessionHasErrors('acc_category_id');

    expect($submission->refresh()->status)->toBe(SubmissionStatus::UnderReview);
});

test('approved hours must be positive', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->patch(route('reviews.update', $review), [
            ...validClassification($category),
            'approved_hours' => 0,
        ])
        ->assertSessionHasErrors('approved_hours');
});

test('the category limit is enforced across approvals for the same student', function () {
    $course = Course::factory()->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $category = AccCategory::factory()->for($course)->create(['max_hours' => 10]);

    $first = submissionFor($student);
    $firstReview = startAndClassify($this, $first, $coordinator, $category, 8);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $firstReview))->assertSessionHasNoErrors();

    $second = submissionFor($student);
    $secondReview = startAndClassify($this, $second, $coordinator, $category, 3);
    reviewActingAs($this, $coordinator)
        ->post(route('reviews.approve', $secondReview))
        ->assertSessionHasErrors('approved_hours');

    expect($first->refresh()->status)->toBe(SubmissionStatus::Approved)
        ->and($second->refresh()->status)->toBe(SubmissionStatus::UnderReview);
});

test('the analysis screen has clear Portuguese labels and accessible form names', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSeeText('Título identificado no comprovante')
        ->assertSeeText('Horas a aproveitar')
        ->assertSee('aria-label="Classificação acadêmica do comprovante"', false)
        ->assertSeeText('Motivo da rejeição');
});

test('a student may create a new submission after a rejection', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => 'Documento ilegível para análise.'])
        ->assertSessionHasNoErrors();

    reviewActingAs($this, $student)
        ->post(route('submissions.store'), ['document' => reviewValidPdf('novo-comprovante.pdf')])
        ->assertSessionHasNoErrors();

    expect(AccSubmission::query()->where('student_affiliation_id', $student->getKey())->count())->toBe(2);
});

/** @return array{AccSubmission, Affiliation, Affiliation, AccCategory} */
function reviewScenario(): array
{
    $course = Course::factory()->create();
    $student = Affiliation::factory()->student()->for($course)->create();
    $coordinator = Affiliation::factory()->coordinator()->for($course)->create();
    $category = AccCategory::factory()->for($course)->create(['max_hours' => 40]);

    return [submissionFor($student), $student, $coordinator, $category];
}

function submissionFor(Affiliation $student): AccSubmission
{
    return AccSubmission::factory()->create([
        'student_affiliation_id' => $student->getKey(),
        'submitted_by_affiliation_id' => $student->getKey(),
    ]);
}

function startReview(TestCase $testCase, AccSubmission $submission, Affiliation $coordinator): AccReview
{
    reviewActingAs($testCase, $coordinator)
        ->post(route('submissions.review.store', $submission))
        ->assertSessionHasNoErrors();

    return $submission->refresh()->review;
}

function startAndClassify(
    TestCase $testCase,
    AccSubmission $submission,
    Affiliation $coordinator,
    AccCategory $category,
    int|float $approvedHours = 8,
): AccReview {
    $review = startReview($testCase, $submission, $coordinator);
    reviewActingAs($testCase, $coordinator)
        ->patch(route('reviews.update', $review), validClassification($category, $approvedHours))
        ->assertSessionHasNoErrors();

    return $review->refresh();
}

/** @return array<string, mixed> */
function validClassification(AccCategory $category, int|float $approvedHours = 8): array
{
    return [
        'original_title' => 'Congresso Tecnologia',
        'normalized_title' => 'Congresso de Tecnologia',
        'original_hours' => 10,
        'approved_hours' => $approvedHours,
        'acc_category_id' => $category->getKey(),
        'classification_justification' => 'Título padronizado para o extrato.',
    ];
}

function reviewActingAs(TestCase $testCase, Affiliation $affiliation): TestCase
{
    $affiliation->load('user');

    return $testCase->actingAs($affiliation->user)
        ->withSession(['active_affiliation_id' => $affiliation->getKey()]);
}

function reviewValidPdf(string $name = 'comprovante.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
}
