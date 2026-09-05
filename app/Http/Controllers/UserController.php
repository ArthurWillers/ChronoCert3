<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Users\CreateInstitutionalUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\SendUserInvitation;
use App\Actions\Users\UpdateUserIdentity;
use App\Enums\AffiliationType;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserIdentityRequest;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateInstitutionalUser $createInstitutionalUser,
        private DeleteUser $deleteUser,
        private SendUserInvitation $sendUserInvitation,
        private UpdateUserIdentity $updateUserIdentity,
    ) {}

    /**
     * Display users available in the selected institutional context.
     */
    public function index(IndexUserRequest $request): View
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $filters = $request->validated();
        $filterAffiliations = function (Builder|HasMany $query) use ($activeAffiliation, $filters): void {
            if ($this->isCoordinator($activeAffiliation)) {
                $query
                    ->where('type', AffiliationType::Student)
                    ->where('course_id', $activeAffiliation->course_id);
            }

            if (($filters['course_id'] ?? null) !== null) {
                $query->where('course_id', $filters['course_id']);
            }

            if (($filters['type'] ?? null) !== null) {
                $query->where('type', $filters['type']);
            }

            if (($filters['status'] ?? null) === 'active') {
                $query->active();
            }

            if (($filters['status'] ?? null) === 'inactive') {
                $query->whereNotNull('deactivated_at');
            }
        };
        $requiresAffiliationScope = $this->isCoordinator($activeAffiliation)
            || ($filters['course_id'] ?? null) !== null
            || ($filters['type'] ?? null) !== null
            || ($filters['status'] ?? null) !== null;
        $query = User::query()->with([
            'affiliations' => function (HasMany $query) use ($filterAffiliations): void {
                $filterAffiliations($query);
                $query
                    ->with('course')
                    ->orderByRaw('deactivated_at IS NULL DESC')
                    ->latest('id');
            },
        ]);

        if ($requiresAffiliationScope) {
            $query->whereHas('affiliations', $filterAffiliations);
        }

        if (($filters['search'] ?? null) !== null) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where('name', 'ilike', $term);
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $courses = Course::query()
            ->when(
                $this->isCoordinator($activeAffiliation),
                fn (Builder $query): Builder => $query->whereKey($activeAffiliation->course_id),
            )
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'activeAffiliation' => $activeAffiliation,
            'courses' => $courses,
            'affiliationTypes' => AffiliationType::options(),
        ]);
    }

    /**
     * Display the new user form with the permitted first-affiliation options.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $activeAffiliation = $this->activeAffiliation($request);

        return view('users.create', [
            'activeAffiliation' => $activeAffiliation->loadMissing('course'),
            'courses' => $this->coursesFor($activeAffiliation),
            'affiliationTypes' => $this->creationTypesFor($activeAffiliation),
        ]);
    }

    /**
     * Create the user, their mandatory initial affiliation, and attempt the invitation.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $activeAffiliation = $this->activeAffiliation($request);
        $data = $request->validated();
        $type = AffiliationType::from($data['affiliation_type']);
        $courseId = $data['course_id'] ?? null;
        $course = $courseId === null ? null : Course::query()->findOrFail($courseId);

        $this->authorize('createNew', [Affiliation::class, $type, $course]);

        if ($this->isCoordinator($activeAffiliation)) {
            abort_unless(
                $type === AffiliationType::Student && (int) $course?->getKey() === (int) $activeAffiliation->course_id,
                403,
            );
        }

        $created = $this->createInstitutionalUser->execute(
            identity: [
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'email' => $data['email'],
            ],
            affiliationData: [
                'type' => $type,
                'course_id' => $course?->getKey(),
                'email' => $data['operational_email'],
                'registration_number' => $data['registration_number'] ?? null,
            ],
            causer: $request->user(),
            activeAffiliation: $activeAffiliation,
        );

        $this->sendUserInvitation->execute(
            user: $created['user'],
            causer: $request->user(),
            activeAffiliation: $activeAffiliation,
        );

        return redirect()
            ->route('users.show', $created['user'])
            ->with('success', 'Usuário e vínculo inicial criados. O convite de acesso foi agendado para envio.');
    }

    /**
     * Display the account and affiliations available to the current role.
     */
    public function show(Request $request, User $user): View
    {
        $this->authorize('view', $user);

        $activeAffiliation = $this->activeAffiliation($request);
        $affiliations = $user->affiliations()
            ->with('course')
            ->when(
                $this->isCoordinator($activeAffiliation),
                fn (Builder $query) => $query
                    ->where('type', AffiliationType::Student)
                    ->where('course_id', $activeAffiliation->course_id),
            )
            ->latest('id')
            ->get();

        return view('users.show', compact('user', 'affiliations', 'activeAffiliation'));
    }

    /**
     * Find an existing user by exact CPF before creating a local student affiliation.
     */
    public function lookup(Request $request): View|RedirectResponse
    {
        $this->authorize('findByCpf', User::class);

        if (! $request->filled('cpf')) {
            return view('users.lookup');
        }

        $validated = Validator::make(
            ['cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')) ?? ''],
            ['cpf' => ['required', 'string', new ValidCpf]],
        )->validate();
        $user = User::query()->where('cpf', $validated['cpf'])->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'cpf' => 'Nenhum usuário foi encontrado com este CPF.',
            ]);
        }

        $request->session()->put('users.affiliation_target_user_id', $user->getKey());

        return view('users.lookup', compact('user'));
    }

    /**
     * Update the identity of a user from the global administrator context.
     */
    public function updateIdentity(UpdateUserIdentityRequest $request, User $user): RedirectResponse
    {
        $this->updateUserIdentity->execute(
            user: $user,
            data: $request->safe()->only(['name', 'cpf', 'email']),
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Dados do usuário atualizados com sucesso.');
    }

    /**
     * Re-send the initial access invitation through Fortify's password-reset flow.
     */
    public function sendInvitation(Request $request, User $user): RedirectResponse
    {
        $this->authorize('sendInvitation', $user);

        $this->sendUserInvitation->execute(
            user: $user,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Convite de acesso agendado para envio.');
    }

    /**
     * Remove an identity only when it has no institutional dependencies.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->deleteUser->execute(
            user: $user,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso.');
    }

    /**
     * @return Collection<int, Course>
     */
    private function coursesFor(Affiliation $activeAffiliation): Collection
    {
        if ($this->isCoordinator($activeAffiliation)) {
            return Course::query()->active()->whereKey($activeAffiliation->course_id)->orderBy('name')->get();
        }

        return Course::query()->active()->orderBy('name')->get();
    }

    /**
     * @return array<string, string>
     */
    private function creationTypesFor(Affiliation $activeAffiliation): array
    {
        return $this->isCoordinator($activeAffiliation)
            ? [AffiliationType::Student->value => AffiliationType::Student->label()]
            : [
                AffiliationType::Administrator->value => AffiliationType::Administrator->label(),
                AffiliationType::Coordinator->value => AffiliationType::Coordinator->label(),
            ];
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
}
