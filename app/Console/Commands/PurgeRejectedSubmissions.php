<?php

namespace App\Console\Commands;

use App\Actions\Submissions\PurgeRejectedSubmission;
use App\Enums\SubmissionStatus;
use App\Models\AccSubmission;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('acc:purge-rejected-submissions')]
#[Description('Remove comprovantes rejeitados cujo prazo de retenção terminou.')]
class PurgeRejectedSubmissions extends Command
{
    public function __construct(private PurgeRejectedSubmission $purgeRejectedSubmission)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $purgedCount = 0;

        AccSubmission::query()
            ->where('status', SubmissionStatus::Rejected)
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', now())
            ->orderBy('id')
            ->eachById(function (AccSubmission $submission) use (&$purgedCount): void {
                if ($this->purgeRejectedSubmission->execute($submission->getKey())) {
                    $purgedCount++;
                }
            }, 100);

        $this->info("{$purgedCount} comprovante(s) expurgado(s).");

        return self::SUCCESS;
    }
}
