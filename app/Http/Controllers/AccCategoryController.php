<?php

namespace App\Http\Controllers;

use App\Actions\Affiliations\ActiveAffiliationContext;
use App\Actions\Categories\CategoryDependencies;
use App\Actions\Categories\ChangeCategoryStatus;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Http\Requests\DeactivateAccCategoryRequest;
use App\Http\Requests\IndexAccCategoryRequest;
use App\Http\Requests\StoreAccCategoryRequest;
use App\Http\Requests\UpdateAccCategoryRequest;
use App\Models\AccCategory;
use App\Models\Affiliation;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccCategoryController extends Controller
{
    public function __construct(
        private ActiveAffiliationContext $activeAffiliationContext,
        private CreateCategory $createCategory,
        private UpdateCategory $updateCategory,
        private ChangeCategoryStatus $changeCategoryStatus,
        private DeleteCategory $deleteCategory,
        private CategoryDependencies $dependencies,
    ) {}

    public function index(IndexAccCategoryRequest $request): View
    {
        $affiliation = $this->activeAffiliation($request);
        $course = $this->activeCourse($request);

        $categories = AccCategory::query()->visibleTo($affiliation)->with('course')
            ->when($course, fn (Builder $query): Builder => $query->whereBelongsTo($course))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($request->validated('search'))).'%';
                $query->where('name', 'ilike', $term);
            })
            ->when($request->input('status') === 'active', fn (Builder $query): Builder => $query->active())
            ->when($request->input('status') === 'inactive', fn (Builder $query): Builder => $query->whereNotNull('deactivated_at'))
            ->orderByRaw('deactivated_at IS NULL DESC')->orderBy('name')->orderBy('id')
            ->paginate(20)->withQueryString();
        $canCreate = $request->user()->can('create', [AccCategory::class, $course]);

        return view('categories.index', compact('categories', 'course', 'canCreate'));
    }

    public function create(IndexAccCategoryRequest $request): View
    {
        $course = $this->activeCourse($request);
        $this->authorize('create', [AccCategory::class, $course]);

        return view('categories.create', compact('course'));
    }

    public function store(StoreAccCategoryRequest $request): RedirectResponse
    {
        $category = $this->createCategory->execute(
            $this->activeCourse($request), $request->validated(),
            $request->user(), $this->activeAffiliation($request),
        );

        return redirect()->route('categories.show', $category)->with('success', 'Categoria criada com sucesso.');
    }

    public function show(Request $request, AccCategory $category): View
    {
        $category->load('course');
        $this->authorize('view', $category);
        $canDelete = $request->user()->can('delete', $category) && $this->dependencies->execute($category) === [];

        return view('categories.show', compact('category', 'canDelete'));
    }

    public function edit(Request $request, AccCategory $category): View
    {
        $category->load('course');
        $this->authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(UpdateAccCategoryRequest $request, AccCategory $category): RedirectResponse
    {
        $this->updateCategory->execute($category, $request->validated(), $request->user(), $this->activeAffiliation($request));

        return redirect()->route('categories.show', $category)->with('success', 'Categoria atualizada com sucesso.');
    }

    public function deactivate(DeactivateAccCategoryRequest $request, AccCategory $category): RedirectResponse
    {
        $this->changeCategoryStatus->execute($category, true, $request->user(), $this->activeAffiliation($request), $request->validated('deactivation_reason'));

        return redirect()->route('categories.show', $category)->with('success', 'Categoria inativada para novas análises.');
    }

    public function reactivate(Request $request, AccCategory $category): RedirectResponse
    {
        $this->authorize('reactivate', $category);
        $this->changeCategoryStatus->execute($category, false, $request->user(), $this->activeAffiliation($request));

        return redirect()->route('categories.show', $category)->with('success', 'Categoria reativada com sucesso.');
    }

    public function destroy(Request $request, AccCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $this->deleteCategory->execute($category, $request->user(), $this->activeAffiliation($request));

        return redirect()->route('categories.index')->with('success', 'Categoria excluída com sucesso.');
    }

    private function activeCourse(Request $request): Course
    {
        $course = $this->activeAffiliation($request)->course()->firstOrFail();
        abort_if($request->filled('course_id') && $request->integer('course_id') !== (int) $course->getKey(), 403);

        return $course;
    }

    private function activeAffiliation(Request $request): Affiliation
    {
        $affiliation = $this->activeAffiliationContext->for($request->user());
        abort_if($affiliation === null, 403);

        return $affiliation;
    }
}
