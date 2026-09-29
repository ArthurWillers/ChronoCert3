<?php

namespace App\Actions\Reviews;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\SubmissionStatus;
use App\Models\AccCategory;
use App\Models\AccReview;
use App\Models\AccSubmission;
use App\Models\Affiliation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ClassifyAccReview
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * @param  array{original_title: string, normalized_title: string, certificate_hours: numeric-string|int|float, is_area_related: bool, acc_category_id: int}  $data
     */
    public function execute(AccReview $review, array $data, Affiliation $reviewerAffiliation, User $causer): AccReview
    {
        return DB::transaction(function () use ($review, $data, $reviewerAffiliation, $causer): AccReview {
            $review = AccReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $submission = AccSubmission::query()
                ->with(['studentAffiliation.course'])
                ->lockForUpdate()
                ->findOrFail($review->acc_submission_id);
            $review->setRelation('submission', $submission);
            $reviewerAffiliation = Affiliation::query()->with(['user', 'course'])->lockForUpdate()->findOrFail($reviewerAffiliation->getKey());
            $category = AccCategory::query()->with('course')->lockForUpdate()->findOrFail($data['acc_category_id']);

            Gate::forUser($causer)->authorize('update', $review);

            if ($submission->status !== SubmissionStatus::UnderReview) {
                throw ValidationException::withMessages(['review' => 'Esta análise já foi concluída.']);
            }

            if (! $category->active()->whereKey($category->getKey())->exists()
                || (int) $category->course_id !== (int) $reviewerAffiliation->course_id) {
                throw ValidationException::withMessages([
                    'acc_category_id' => 'Selecione uma categoria ativa do curso em análise.',
                ]);
            }

            $oldValues = Arr::only($review->getAttributes(), [
                'reviewer_affiliation_id',
                'acc_category_id',
                'normalized_title',
                'certificate_hours',
                'is_area_related',
            ]);
            $review->update([
                'reviewer_affiliation_id' => $reviewerAffiliation->getKey(),
                'acc_category_id' => $category->getKey(),
                'original_title' => trim($data['original_title']),
                'normalized_title' => trim($data['normalized_title']),
                'certificate_hours' => $data['certificate_hours'],
                'is_area_related' => $data['is_area_related'],
                'classification_justification' => null,
            ]);

            $this->recordActivity->execute(
                event: AuditEvent::SubmissionReclassified,
                subject: $submission,
                causer: $causer,
                activeAffiliation: $reviewerAffiliation,
                contextCourseId: $reviewerAffiliation->course_id,
                references: [
                    'course' => $submission->studentAffiliation->course,
                    'student_affiliation' => $submission->studentAffiliation,
                    'reviewer_affiliation' => $reviewerAffiliation,
                    'category' => $category,
                    'review' => $review,
                ],
                changes: collect(Arr::only($review->getAttributes(), array_keys($oldValues)))
                    ->mapWithKeys(fn (mixed $value, string $attribute): array => [
                        $attribute => ['old' => $oldValues[$attribute] ?? null, 'new' => $value],
                    ])->all(),
            );

            return $review->refresh();
        });
    }
}
