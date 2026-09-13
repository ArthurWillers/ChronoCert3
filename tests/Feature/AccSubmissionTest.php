<?php

use App\Enums\SubmissionOrigin;
use App\Enums\SubmissionStatus;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\AuditActivity;
use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('private');
});

test('a student can open the proof submission form', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();

    actingAsAffiliation($this, $studentAffiliation)
        ->get(route('submissions.create'))
        ->assertOk()
        ->assertSeeText('Enviar comprovante de ACC');
});

test('a student submits a proof for the active student affiliation', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();

    $response = actingAsAffiliation($this, $studentAffiliation)
        ->post(route('submissions.store'), ['document' => validPdf()]);

    $response->assertSessionHasNoErrors();
    $submission = AccSubmission::query()->with('media')->sole();

    $response->assertRedirect(route('submissions.show', $submission));
    expect($submission)
        ->student_affiliation_id->toBe($studentAffiliation->getKey())
        ->submitted_by_affiliation_id->toBe($studentAffiliation->getKey())
        ->origin->toBe(SubmissionOrigin::Student)
        ->status->toBe(SubmissionStatus::Submitted);
});

test('a coordinator registers a proof for an active student in the same course', function () {
    $course = Course::factory()->create();
    $studentAffiliation = Affiliation::factory()->student()->for($course)->create();
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->for($course)->create();

    $response = actingAsAffiliation($this, $coordinatorAffiliation)
        ->post(route('submissions.students.store', $studentAffiliation), ['document' => validPdf()]);

    $submission = AccSubmission::query()->sole();

    $response->assertRedirect(route('submissions.show', $submission));
    expect($submission)
        ->student_affiliation_id->toBe($studentAffiliation->getKey())
        ->submitted_by_affiliation_id->toBe($coordinatorAffiliation->getKey())
        ->origin->toBe(SubmissionOrigin::Coordinator)
        ->status->toBe(SubmissionStatus::Submitted);
});

test('a coordinator cannot register a proof for a student from another course', function () {
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->create();
    $studentAffiliation = Affiliation::factory()->student()->create();

    actingAsAffiliation($this, $coordinatorAffiliation)
        ->post(route('submissions.students.store', $studentAffiliation), ['document' => validPdf()])
        ->assertForbidden();

    expect(AccSubmission::query()->count())->toBe(0);
});

test('an inactive affiliation cannot submit a proof', function () {
    $studentAffiliation = Affiliation::factory()->student()->inactive()->create();

    actingAsAffiliation($this, $studentAffiliation)
        ->post(route('submissions.store'), ['document' => validPdf()])
        ->assertForbidden();

    expect(AccSubmission::query()->count())->toBe(0);
});

test('the upload rejects an invalid detected MIME type', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();

    actingAsAffiliation($this, $studentAffiliation)
        ->from(route('submissions.create'))
        ->post(route('submissions.store'), [
            'document' => UploadedFile::fake()->createWithContent('evidence.pdf', 'not a PDF file'),
        ])
        ->assertRedirect(route('submissions.create'))
        ->assertSessionHasErrors('document');

    actingAsAffiliation($this, $studentAffiliation)
        ->from(route('submissions.create'))
        ->post(route('submissions.store'), [
            'document' => validPdf('evidence.exe'),
        ])
        ->assertRedirect(route('submissions.create'))
        ->assertSessionHasErrors('document');
});

test('the upload rejects an empty file and a file larger than ten megabytes', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();

    actingAsAffiliation($this, $studentAffiliation)
        ->from(route('submissions.create'))
        ->post(route('submissions.store'), ['document' => UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf')])
        ->assertRedirect(route('submissions.create'))
        ->assertSessionHasErrors('document');

    actingAsAffiliation($this, $studentAffiliation)
        ->from(route('submissions.create'))
        ->post(route('submissions.store'), ['document' => UploadedFile::fake()->create('large.pdf', 10 * 1024 + 1, 'application/pdf')])
        ->assertRedirect(route('submissions.create'))
        ->assertSessionHasErrors('document');
});

test('the proof is stored privately with original and technical metadata', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();

    actingAsAffiliation($this, $studentAffiliation)
        ->post(route('submissions.store'), ['document' => validPdf('atividade-complementar.pdf')]);

    $submission = AccSubmission::query()->with('media')->sole();
    $media = $submission->getFirstMedia(AccSubmission::EvidenceCollection);

    expect($media)
        ->not->toBeNull()
        ->disk->toBe('private')
        ->getCustomProperty('original_filename')->toBe('atividade-complementar.pdf')
        ->getCustomProperty('detected_mime_type')->toBe('application/pdf')
        ->getCustomProperty('size_bytes')->toBeGreaterThan(0)
        ->getCustomProperty('sha256')->not->toBeEmpty();
    Storage::disk('private')->assertExists($media->getPathRelativeToRoot());
});

test('the beneficiary can download the protected proof', function () {
    $studentAffiliation = Affiliation::factory()->student()->create();
    $submission = submitProof($this, $studentAffiliation);

    actingAsAffiliation($this, $studentAffiliation)
        ->get(route('submissions.download', $submission))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('a coordinator from the same course can view and download the protected proof', function () {
    $course = Course::factory()->create();
    $studentAffiliation = Affiliation::factory()->student()->for($course)->create();
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->for($course)->create();
    $submission = submitProof($this, $studentAffiliation);

    actingAsAffiliation($this, $coordinatorAffiliation)
        ->get(route('submissions.document', $submission))
        ->assertOk();
    actingAsAffiliation($this, $coordinatorAffiliation)
        ->get(route('submissions.download', $submission))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('a student cannot view or download another students proof', function () {
    $course = Course::factory()->create();
    $ownerAffiliation = Affiliation::factory()->student()->for($course)->create();
    $otherStudentAffiliation = Affiliation::factory()->student()->for($course)->create();
    $submission = submitProof($this, $ownerAffiliation);

    actingAsAffiliation($this, $otherStudentAffiliation)
        ->get(route('submissions.document', $submission))
        ->assertForbidden();
    actingAsAffiliation($this, $otherStudentAffiliation)
        ->get(route('submissions.download', $submission))
        ->assertForbidden();
});

test('submission activities identify the author affiliation, beneficiary, course and transition without sensitive values', function () {
    $course = Course::factory()->create();
    $studentAffiliation = Affiliation::factory()->student()->for($course)->create();
    $coordinatorAffiliation = Affiliation::factory()->coordinator()->for($course)->create();

    actingAsAffiliation($this, $coordinatorAffiliation)
        ->post(route('submissions.students.store', $studentAffiliation), ['document' => validPdf()]);

    $activity = AuditActivity::query()->sole();
    $properties = $activity->properties->all();

    $studentAffiliation->load('user');
    $coordinatorAffiliation->load('user');

    expect($activity)
        ->event->toBe('submission.uploaded_by_coordinator')
        ->context_course_id->toBe($course->getKey());
    expect(data_get($properties, 'references.student_affiliation.id'))->toBe($studentAffiliation->getKey());
    expect(data_get($properties, 'references.submitted_by_affiliation.id'))->toBe($coordinatorAffiliation->getKey());
    expect(data_get($properties, 'changes.status.new'))->toBe(SubmissionStatus::Submitted->value);
    expect(json_encode($properties, JSON_THROW_ON_ERROR))
        ->not->toContain($studentAffiliation->user->cpf)
        ->not->toContain($studentAffiliation->email)
        ->not->toContain($coordinatorAffiliation->email);
});

function validPdf(string $name = 'comprovante.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
}

function actingAsAffiliation(TestCase $testCase, Affiliation $affiliation): TestCase
{
    $affiliation->load('user');

    return $testCase->actingAs($affiliation->user)
        ->withSession(['active_affiliation_id' => $affiliation->getKey()]);
}

function submitProof(TestCase $testCase, Affiliation $studentAffiliation): AccSubmission
{
    actingAsAffiliation($testCase, $studentAffiliation)
        ->post(route('submissions.store'), ['document' => validPdf()])
        ->assertRedirect();

    return AccSubmission::query()->latest('id')->firstOrFail();
}
