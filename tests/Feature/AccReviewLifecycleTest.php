<?php

use App\Actions\Statements\BuildStudentAccSummary;
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
        ->certificate_hours->toBe('10.00')
        ->is_area_related->toBeTrue()
        ->acc_category_id->toBe($category->getKey())
        ->classification_justification->toBeNull();
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
        ->and(data_get($review->rules_snapshot, 'certificate_hours'))->toBe('10.00');
});

test('a coordinator accepts a document and saves its classification in one submission', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.complete', $review), [
            ...validClassification($category),
            'decision' => 'approve',
        ])
        ->assertRedirect(route('submissions.show', $submission))
        ->assertSessionHasNoErrors();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved)
        ->and($review->refresh())
        ->normalized_title->toBe('Congresso de Tecnologia')
        ->completed_at->not->toBeNull();
});

test('a rejection requires a reason and preserves the document', function () {
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
        ->purge_at->toBeNull();
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

test('a coordinator can reopen an approval, correct it and reject it', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startAndClassify($this, $submission, $coordinator, $category, 10);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $review))->assertSessionHasNoErrors();
    expect(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(10.0);

    reviewActingAs($this, $student)->post(route('reviews.reopen', $review))->assertForbidden();
    $otherCoordinator = Affiliation::factory()->coordinator()->for(Course::factory()->create())->create();
    reviewActingAs($this, $otherCoordinator)->post(route('reviews.reopen', $review))->assertForbidden();
    reviewActingAs($this, $coordinator)->get(route('submissions.show', $submission))->assertSeeText('Reabrir análise');
    reviewActingAs($this, $coordinator)->post(route('reviews.reopen', $review))->assertSessionHasNoErrors();
    expect($submission->refresh()->status)->toBe(SubmissionStatus::UnderReview)
        ->and(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(0.0);
    reviewActingAs($this, $coordinator)->post(route('reviews.complete', $review), [
        ...validClassification($category, 7), 'decision' => 'approve',
    ])->assertSessionHasNoErrors();
    expect(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(7.0);
    reviewActingAs($this, $coordinator)->post(route('reviews.reopen', $review))->assertSessionHasNoErrors();
    reviewActingAs($this, $coordinator)->post(route('reviews.reject', $review), [
        'rejection_reason' => 'Documento não corresponde à atividade.',
    ])->assertSessionHasNoErrors();
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Rejected)
        ->and(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(0.0);
    $this->assertDatabaseHas('activity_log', ['event' => 'submission.review_reopened', 'subject_id' => $submission->getKey()]);
});

test('a rejected document cannot be reopened while an identical active submission exists', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $submission->update(['document_sha256' => str_repeat('a', 64)]);
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)->post(route('reviews.reject', $review), [
        'rejection_reason' => 'Documento ilegível para análise.',
    ])->assertSessionHasNoErrors();
    submissionFor($student)->update(['document_sha256' => str_repeat('a', 64)]);

    reviewActingAs($this, $coordinator)->post(route('reviews.reopen', $review))->assertSessionHasErrors('review');
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Rejected);
});

test('a coordinator can reopen and accept a rejected document', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)->post(route('reviews.reject', $review), [
        'rejection_reason' => 'Documento ilegível para análise.',
    ])->assertSessionHasNoErrors();
    reviewActingAs($this, $coordinator)->post(route('reviews.reopen', $review))->assertSessionHasNoErrors();
    expect($submission->refresh()->purge_at)->toBeNull()
        ->and($review->refresh()->rejection_reason)->toBeNull();
    reviewActingAs($this, $coordinator)->post(route('reviews.complete', $review), [
        ...validClassification($category, 8), 'decision' => 'approve',
    ])->assertSessionHasNoErrors();
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved)
        ->and(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(8.0);
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

test('certificate hours must be positive', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->patch(route('reviews.update', $review), [
            ...validClassification($category),
            'certificate_hours' => 0,
        ])
        ->assertSessionHasErrors('certificate_hours');
});

test('certificate hours accept Portuguese decimals and reject precision that would round to zero', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)->patch(route('reviews.update', $review), [
        ...validClassification($category), 'certificate_hours' => '0.001',
    ])->assertSessionHasErrors('certificate_hours');
    reviewActingAs($this, $coordinator)->post(route('reviews.complete', $review), [
        ...validClassification($category), 'decision' => 'approve', 'certificate_hours' => '12,5', 'is_area_related' => false,
    ])->assertSessionHasNoErrors();

    expect($review->refresh()->certificate_hours)->toBe('12.50')
        ->and($review->is_area_related)->toBeFalse()
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
});

test('rejection ignores incomplete or invalid acceptance fields', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    reviewActingAs($this, $coordinator)->post(route('reviews.complete', $review), [
        'decision' => 'reject', 'certificate_hours' => 'inválido', 'acc_category_id' => 'inválida',
        'rejection_reason' => 'O arquivo não permite conferir a atividade.',
    ])->assertSessionHasNoErrors();
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Rejected);
});

test('category limit changes preserve recognized hours and do not retroactively recognize old excess', function () {
    [$first, $student, $coordinator, $category] = reviewScenario();
    $category->update(['max_hours' => 20]);
    $firstReview = startAndClassify($this, $first, $coordinator, $category, 30);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $firstReview))->assertSessionHasNoErrors();
    expect($firstReview->refresh()->rules_snapshot['recognized_hours'])->toBe('20.00');

    $category->update(['max_hours' => 10]);
    expect(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(20.0);
    $secondReview = startAndClassify($this, submissionFor($student), $coordinator, $category, 5);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $secondReview))->assertSessionHasNoErrors();
    expect($secondReview->refresh()->rules_snapshot['recognized_hours'])->toBe('0.00');

    $category->update(['max_hours' => 50]);
    expect(app(BuildStudentAccSummary::class)->execute($student)['recognizedHours'])->toBe(20.0);
    $thirdReview = startAndClassify($this, submissionFor($student), $coordinator, $category, 5);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $thirdReview))->assertSessionHasNoErrors();
    $summary = app(BuildStudentAccSummary::class)->execute($student);
    expect($summary['recognizedHours'])->toBe(25.0)
        ->and($summary['totalCertificateHours'])->toBe(40.0);
});

test('area hours show the full recognized amount even above the course minimum', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $student->course->update(['required_acc_hours' => 60, 'minimum_area_percentage' => 50]);
    $category->update(['max_hours' => 60]);
    $review = startAndClassify($this, $submission, $coordinator, $category, 45);
    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $review))->assertSessionHasNoErrors();
    $summary = app(BuildStudentAccSummary::class)->execute($student);
    expect($summary['recognizedAreaHours'])->toBe(45.0)->and($summary['minimumAreaHours'])->toBe(30.0);
});

test('all certificate hours are accepted even when the category total exceeds its limit', function () {
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
        ->assertSessionHasNoErrors();

    expect($first->refresh()->status)->toBe(SubmissionStatus::Approved)
        ->and($second->refresh()->status)->toBe(SubmissionStatus::Approved);

    $summary = app(BuildStudentAccSummary::class)->execute($student);

    expect($summary['totalCertificateHours'])->toBe(11.0)
        ->and($summary['recognizedHours'])->toBe(10.0)
        ->and($summary['categorySummaries']->sole()['recognized_hours'])->toBe(10.0);
});

test('the analysis screen has clear Portuguese labels and accessible form names', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    startReview($this, $submission, $coordinator);

    reviewActingAs($this, $coordinator)
        ->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertSeeText('Título identificado no documento')
        ->assertSeeText('Carga horária do certificado')
        ->assertSeeText('Atividade relacionada à área de formação do curso')
        ->assertSee('aria-label="Decisão acadêmica do documento"', false)
        ->assertSeeText('Motivo da rejeição')
        ->assertDontSeeText('Salvar classificação')
        ->assertDontSeeText('Justificativa da classificação');
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
    int|float $certificateHours = 10,
): AccReview {
    $review = startReview($testCase, $submission, $coordinator);
    reviewActingAs($testCase, $coordinator)
        ->patch(route('reviews.update', $review), validClassification($category, $certificateHours))
        ->assertSessionHasNoErrors();

    return $review->refresh();
}

/** @return array<string, mixed> */
function validClassification(AccCategory $category, int|float $certificateHours = 10): array
{
    return [
        'original_title' => 'Congresso Tecnologia',
        'normalized_title' => 'Congresso de Tecnologia',
        'certificate_hours' => $certificateHours,
        'is_area_related' => true,
        'acc_category_id' => $category->getKey(),
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
