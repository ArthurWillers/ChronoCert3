<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Courses\ChangeCourseStatus;
use App\Actions\Courses\CreateCourse;
use App\Actions\Courses\DeleteCourse;
use App\Actions\Courses\UpdateCourse;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateCourse $createCourse,
        private UpdateCourse $updateCourse,
        private ChangeCourseStatus $changeCourseStatus,
        private DeleteCourse $deleteCourse,
    ) {}

    /**
     * Display the institutional course configuration.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::query()
            ->withCount('affiliations')
            ->when(
                $request->filled('search'),
                function (Builder $query) use ($request): void {
                    $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('search'))).'%';

                    $query->where('name', 'ilike', $term);
                },
            )
            ->when($request->input('status') === 'active', fn (Builder $query): Builder => $query->active())
            ->when($request->input('status') === 'inactive', fn (Builder $query): Builder => $query->whereNotNull('deactivated_at'))
            ->orderByRaw('deactivated_at IS NULL DESC')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    /**
     * Display the course creation form.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Course::class);

        return view('courses.create');
    }

    /**
     * Store a course.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->createCourse->execute(
            data: $request->validated(),
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return redirect()->route('courses.edit', $course)->with('success', 'Curso criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course): RedirectResponse
    {
        return redirect()->route('courses.edit', $course);
    }

    /**
     * Display the course edit form.
     */
    public function edit(Request $request, Course $course): View
    {
        $this->authorize('update', $course);

        return view('courses.edit', compact('course'));
    }

    /**
     * Update a course.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->updateCourse->execute(
            course: $course,
            data: $request->validated(),
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Curso atualizado com sucesso.');
    }

    /**
     * Remove a course without domain dependencies.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $this->deleteCourse->execute(
            course: $course,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return redirect()->route('courses.index')->with('success', 'Curso excluído com sucesso.');
    }

    /**
     * Make a course unavailable for new institutional operations.
     */
    public function deactivate(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('deactivate', $course);

        $this->changeCourseStatus->execute(
            course: $course,
            deactivate: true,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Curso inativado. Novos vínculos não serão aceitos.');
    }

    /**
     * Return a course to operation.
     */
    public function reactivate(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('reactivate', $course);

        $this->changeCourseStatus->execute(
            course: $course,
            deactivate: false,
            causer: $request->user(),
            activeAffiliation: $this->activeAffiliation($request),
        );

        return back()->with('success', 'Curso reativado com sucesso.');
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());

        abort_if($affiliation === null, 403);

        return $affiliation;
    }
}
