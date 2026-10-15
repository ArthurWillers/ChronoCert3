<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvalidateUserSessions
{
    public function __construct(private Request $request) {}

    public function execute(User $user, bool $keepCurrentSession = false): void
    {
        $user->setRememberToken(Str::random(60));
        $user->save();

        if (config('session.driver') !== 'database') {
            return;
        }

        $currentSessionId = $keepCurrentSession && $this->request->hasSession()
            ? $this->request->session()->getId()
            : null;

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when($currentSessionId, fn (Builder $query): Builder => $query->where('id', '!=', $currentSessionId))
            ->delete();
    }
}
