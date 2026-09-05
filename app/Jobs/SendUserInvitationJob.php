<?php

namespace App\Jobs;

use App\Actions\Audit\RecordActivity;
use App\Enums\AuditEvent;
use App\Models\Affiliation;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class SendUserInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly int $causerId,
        public readonly ?int $activeAffiliationId,
    ) {}

    public function handle(RecordActivity $recordActivity): void
    {
        $user = User::query()->findOrFail($this->userId);
        $causer = User::query()->findOrFail($this->causerId);
        $activeAffiliation = $this->activeAffiliationId === null
            ? null
            : Affiliation::query()->with('course')->findOrFail($this->activeAffiliationId);
        $token = Password::broker()->createToken($user);

        $user->notify(new AccountInvitation($token));

        DB::transaction(function () use ($recordActivity, $user, $causer, $activeAffiliation): void {
            $recordActivity->execute(
                event: AuditEvent::UserInvitationSent,
                subject: $user,
                causer: $causer,
                activeAffiliation: $activeAffiliation,
                references: ['user' => $user],
            );
        });
    }
}
