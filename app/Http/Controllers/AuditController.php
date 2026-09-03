<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Enums\AuditEvent;
use App\Http\Requests\IndexAuditRequest;
use App\Models\AuditActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __construct(private ActiveAffiliationContext $activeAffiliationContext) {}

    /**
     * Display the audit records available in the selected affiliation context.
     */
    public function index(IndexAuditRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $affiliation = $this->activeAffiliationContext->for($user);

        abort_if($affiliation === null, 403);

        $visibleActivities = AuditActivity::query()->visibleTo($affiliation);
        $activities = $this->applyFilters(clone $visibleActivities, $request)
            ->with([
                'causer' => function (MorphTo $morphTo): void {
                    $morphTo->constrain([
                        User::class => function (Builder $query): void {
                            $query->select('id', 'name');
                        },
                    ]);
                },
            ])
            ->when(
                $request->string('sort')->toString() === 'oldest',
                fn (Builder $query): Builder => $query->oldest()->orderBy('id'),
                fn (Builder $query): Builder => $query->latest()->orderByDesc('id'),
            )
            ->paginate(50)
            ->withQueryString();

        $availableAreas = (clone $visibleActivities)
            ->whereNotNull('log_name')
            ->distinct()
            ->pluck('log_name')
            ->all();
        $areas = collect(AuditEvent::cases())
            ->filter(static fn (AuditEvent $event): bool => in_array($event->area(), $availableAreas, true))
            ->unique(static fn (AuditEvent $event): string => $event->area())
            ->values();
        $events = (clone $visibleActivities)
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->map(static fn (string $event): ?AuditEvent => AuditEvent::tryFrom($event))
            ->filter()
            ->values();
        $causerIds = (clone $visibleActivities)
            ->where('causer_type', (new User)->getMorphClass())
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');
        $causers = User::query()
            ->select('id', 'name')
            ->whereIn('id', $causerIds)
            ->orderBy('name')
            ->get();

        return view('audit.index', compact('activities', 'areas', 'events', 'causers'));
    }

    /**
     * Display one audit record without resolving protected or deleted subjects.
     */
    public function show(AuditActivity $auditActivity): View
    {
        $this->authorize('view', $auditActivity);

        $auditActivity->load([
            'causer' => function (MorphTo $morphTo): void {
                $morphTo->constrain([
                    User::class => function (Builder $query): void {
                        $query->select('id', 'name');
                    },
                ]);
            },
        ]);

        return view('audit.show', compact('auditActivity'));
    }

    /**
     * @param  Builder<AuditActivity>  $query
     * @return Builder<AuditActivity>
     */
    private function applyFilters(Builder $query, IndexAuditRequest $request): Builder
    {
        return $query
            ->when(
                $request->filled('area'),
                fn (Builder $query): Builder => $query->where('log_name', $request->string('area')->toString()),
            )
            ->when(
                $request->filled('event'),
                fn (Builder $query): Builder => $query->where('event', $request->string('event')->toString()),
            )
            ->when($request->input('causer') === 'system', function (Builder $query): Builder {
                return $query->whereNull('causer_id');
            })
            ->when(
                $request->filled('causer') && $request->input('causer') !== 'system',
                fn (Builder $query): Builder => $query->where('causer_id', (int) $request->input('causer')),
            )
            ->when(
                $request->filled('date_start'),
                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $request->date('date_start')),
            )
            ->when(
                $request->filled('date_end'),
                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $request->date('date_end')),
            )
            ->when(
                $request->filled('course_id'),
                fn (Builder $query): Builder => $query->where('context_course_id', $request->integer('course_id')),
            );
    }
}
