<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Affiliations\ChangeAffiliationStatus;
use App\Actions\Affiliations\CreateAffiliation;
use App\Actions\Affiliations\UpdateAffiliation;
use App\Enums\AffiliationType;
use App\Http\Requests\DeactivateAffiliationRequest;
use App\Http\Requests\StoreUserAffiliationRequest;
use App\Http\Requests\UpdateAffiliationRequest;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAffiliationController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateAffiliation $createAffiliation,
        private UpdateAffiliation $updateAffiliation,
        private ChangeAffiliationStatus $changeAffiliationStatus,
    ) {}

    /**
     * Display the contextual form to add an affiliation to an existing user.
     */
    public function create(Request $request, User $user): View
    {
        $this->authorize('addAffiliation', $user);

        $activeAffiliation = $this->activeAffiliation($request);
        $this->ensureCoordinatorTargetWasLookedUp($request, $user, $activeAffiliation);

        return view('users.affiliations.create', [
            'user' => $user,
            'activeAffiliation' => $activeAffiliation->loadMissing('course'),
            'courses' => $this->coursesFor($activeAffiliation),
            'affiliationTypes' => $this->creationTypesFor($activeAffiliation),
        ]);
    }

    /**
     * Create a new affiliation for an existing user.
     */
    public function store(StoreUserAffiliationRequest $request, User $user): RedirectResponse
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $this->ensureCoordinatorTargetWasLookedUp($request, $user, $activeAffiliation);
        $data = $request->validated();
        $type = AffiliationType::from($data['affiliation_type']);
        $courseId = $data['course_id'] ?? null;
        $course = $courseId === null ? null : Course::query()->findOrFail($courseId);

        $this->authorize('createFor', [Affiliation::class, $user, $type, $course, false]);

        if ($this->isCoordinator($activeAffiliation)) {
            abort_unless(
                $type === AffiliationType::Student && (int) $course?->getKey() === (int) $activeAffiliation->course_id,
                403,
            );
        }

        $this->createAffiliation->execute(
            targetUser: $user,
            data: [
                'type' => $type,
                'course_id' => $course?->getKey(),
                'email' => $data['operational_email'],
                'registration_number' => $data['registration_number'] ?? null,
            ],
            causer: $request->user(),
            activeAffiliation: $activeAffiliation,
        );

        $request->session()->forget('users.affiliation_target_user_id');

        return redirect()->route('users.show', $user)->with('success', 'Vínculo criado com sucesso.');
    }

    /**
     * Display an editable affiliation in the selected scope.
     */
    public function edit(Request $request, User $user, Affiliation $affiliation): View
    {
        $this->ensureAffiliationBelongsToUser($user, $affiliation);
        $this->authorize('update', $affiliation);
        $activeAffiliation = $this->activeAffiliation($request);
        $canChangeCourse = $this->isGlobalAdministrator($activeAffiliation)
            && $affiliation->type === AffiliationType::Coordinator;

        return view('users.affiliations.edit', [
            'user' => $user,
            'affiliation' => $affiliation->loadMissing('course'),
            'courses' => $canChangeCourse ? Course::query()->active()->orderBy('name')->get() : collect(),
            'canChangeCourse' => $canChangeCourse,
        ]);
    }

    /**
     * Update the operational data of an affiliation.
     */
    public function update(UpdateAffiliationRequest $request, User $user, Affiliation $affiliation): RedirectResponse
    {
        $this->ensureAffiliationBelongsToUser($user, $affiliation);
        $data = $request->validated();

        $this->updateAffiliation->execute(
            affiliation: $affiliation,
            data: [
                'email' => $data['operational_email'],
                'registration_number' => $data['registration_number'],
                'course_id' => $affiliation->type === AffiliationType::Coordinator
                    ? $data['course_id']
                    : $affiliation->course_id,
            ],
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return redirect()->route('users.show', $user)->with('success', 'Vínculo atualizado com sucesso.');
    }

    /**
     * Deactivate an affiliation without deleting its history.
     */
    public function deactivate(DeactivateAffiliationRequest $request, User $user, Affiliation $affiliation): RedirectResponse
    {
        $this->ensureAffiliationBelongsToUser($user, $affiliation);

        $this->changeAffiliationStatus->execute(
            affiliation: $affiliation,
            deactivate: true,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'Vínculo desativado com sucesso.');
    }

    /**
     * Reactivate an affiliation whose course remains active.
     */
    public function activate(Request $request, User $user, Affiliation $affiliation): RedirectResponse
    {
        $this->ensureAffiliationBelongsToUser($user, $affiliation);
        $this->authorize('activate', $affiliation);

        $this->changeAffiliationStatus->execute(
            affiliation: $affiliation,
            deactivate: false,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Vínculo reativado com sucesso.');
    }

    /**
     * @return Collection<int, Course>
     */
    private function coursesFor(Affiliation $activeAffiliation): Collection
    {
        if ($this->isCoordinator($activeAffiliation)) {
            return Course::query()->active()->whereKey($activeAffiliation->course_id)->get();
        }

        return Course::query()->active()->orderBy('name')->get();
    }

    /**
     * @return array<string, string>
     */
    private function creationTypesFor(Affiliation $activeAffiliation): array
    {
        if ($this->isCoordinator($activeAffiliation)) {
            return [AffiliationType::Student->value => AffiliationType::Student->label()];
        }

        return [
            AffiliationType::Administrator->value => AffiliationType::Administrator->label(),
            AffiliationType::Coordinator->value => AffiliationType::Coordinator->label(),
        ];
    }

    private function ensureCoordinatorTargetWasLookedUp(Request $request, User $user, Affiliation $activeAffiliation): void
    {
        if (! $this->isCoordinator($activeAffiliation)) {
            return;
        }

        abort_unless(
            (int) $request->session()->get('users.affiliation_target_user_id') === (int) $user->getKey(),
            403,
        );
    }

    private function ensureAffiliationBelongsToUser(User $user, Affiliation $affiliation): void
    {
        abort_unless((int) $affiliation->user_id === (int) $user->getKey(), 404);
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());

        abort_if($affiliation === null, 403);

        return $affiliation;
    }

    private function isCoordinator(Affiliation $affiliation): bool
    {
        return $affiliation->type === AffiliationType::Coordinator && $affiliation->course_id !== null;
    }

    private function isGlobalAdministrator(Affiliation $affiliation): bool
    {
        return $affiliation->type === AffiliationType::Administrator && $affiliation->course_id === null;
    }
}
