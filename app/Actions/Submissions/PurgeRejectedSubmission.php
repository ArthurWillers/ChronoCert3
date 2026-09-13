<?php

namespace App\Actions\Submissions;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Enums\AuditSource;
use App\Enums\SubmissionStatus;
use App\Models\AccSubmission;
use Illuminate\Support\Facades\DB;

class PurgeRejectedSubmission
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function execute(int $submissionId): bool
    {
        return DB::transaction(function () use ($submissionId): bool {
            $submission = AccSubmission::query()
                ->with(['studentAffiliation.user', 'studentAffiliation.course', 'submittedByAffiliation', 'review'])
                ->lockForUpdate()
                ->find($submissionId);

            if ($submission === null
                || $submission->status !== SubmissionStatus::Rejected
                || $submission->purge_at === null
                || $submission->purge_at->isFuture()) {
                return false;
            }

            $this->recordActivity->execute(
                event: AuditEvent::SubmissionPurged,
                subject: $submission,
                contextCourseId: $submission->studentAffiliation->course_id,
                references: [
                    'course' => $submission->studentAffiliation->course,
                    'student_affiliation' => $submission->studentAffiliation,
                    'submitted_by_affiliation' => $submission->submittedByAffiliation,
                    'review' => $submission->review ?? ['id' => null, 'type' => 'review'],
                ],
                changes: [
                    'status' => ['old' => SubmissionStatus::Rejected->value, 'new' => 'purged'],
                    'purge_at' => ['old' => $submission->purge_at->toIso8601String(), 'new' => null],
                ],
                source: AuditSource::Scheduled,
                sourceDetail: 'acc:purge-rejected-submissions',
            );

            $submission->clearMediaCollection(AccSubmission::EvidenceCollection);
            $submission->review()?->delete();
            $submission->delete();

            return true;
        });
    }
}
