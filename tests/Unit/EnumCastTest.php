<?php

use App\Enums\AffiliationType;
use App\Enums\SubmissionOrigin;
use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;

test('affiliation types use their expected persisted values', function () {
    expect(AffiliationType::cases())
        ->toBe([
            AffiliationType::STUDENT,
            AffiliationType::COORDINATOR,
            AffiliationType::ADMINISTRATOR,
        ])
        ->and(AffiliationType::values())
        ->toBe(['student', 'coordinator', 'administrator'])
        ->and(AffiliationType::options())
        ->toBe([
            'student' => 'Aluno',
            'coordinator' => 'Coordenador',
            'administrator' => 'Administrador',
        ])
        ->and(AffiliationType::COORDINATOR->label())
        ->toBe('Coordenador');
});

test('submission statuses use their expected persisted values', function () {
    expect(SubmissionStatus::values())
        ->toBe(['submitted', 'under_review', 'rejected', 'approved'])
        ->and(SubmissionStatus::options())
        ->toBe([
            'submitted' => 'Enviado',
            'under_review' => 'Em análise',
            'rejected' => 'Rejeitado',
            'approved' => 'Aprovado',
        ])
        ->and(SubmissionStatus::UNDER_REVIEW->label())
        ->toBe('Em análise');
});

test('submission origins use their expected persisted values', function () {
    expect(SubmissionOrigin::values())
        ->toBe(['student', 'coordinator'])
        ->and(SubmissionOrigin::options())
        ->toBe([
            'student' => 'Aluno',
            'coordinator' => 'Coordenador',
        ])
        ->and(SubmissionOrigin::COORDINATOR->label())
        ->toBe('Coordenador');
});

test('eloquent serializes and casts enum values', function () {
    $affiliation = new AffiliationForCastTest;
    $affiliation->type = AffiliationType::STUDENT;

    $submission = new AccSubmissionForCastTest;
    $submission->status = SubmissionStatus::UNDER_REVIEW;
    $submission->origin = SubmissionOrigin::COORDINATOR;

    expect($affiliation->getAttributes()['type'])->toBe('student')
        ->and($affiliation->type)->toBe(AffiliationType::STUDENT)
        ->and($submission->getAttributes())
        ->toMatchArray([
            'status' => 'under_review',
            'origin' => 'coordinator',
        ])
        ->and($submission->status)->toBe(SubmissionStatus::UNDER_REVIEW)
        ->and($submission->origin)->toBe(SubmissionOrigin::COORDINATOR);
});

class AffiliationForCastTest extends Model
{
    protected function casts(): array
    {
        return [
            'type' => AffiliationType::class,
        ];
    }
}

class AccSubmissionForCastTest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'origin' => SubmissionOrigin::class,
        ];
    }
}
